import os
import re
import math
import json
import random
from collections import Counter, defaultdict
import pandas as pd

def tokenize(text):
    return re.findall(r'\b[a-zA-Z]{2,}\b', text.lower())

def train_and_export():
    base_dir = os.path.dirname(os.path.abspath(__file__))
    
    # Recherche du dossier de donnees
    possible_data_dirs = [
        os.path.abspath(os.path.join(base_dir, '..', 'data', 'sampled_train')),
        os.path.abspath(os.path.join(base_dir, '..', '..', '..', 'TPfiltre', 'sampled_train')),
        os.path.abspath('sampled_train'),
        os.path.abspath(os.path.join('includes', 'filter', 'data', 'sampled_train'))
    ]

    data_dir = None
    for candidate in possible_data_dirs:
        if os.path.isdir(candidate) and os.path.exists(os.path.join(candidate, 'annotations_metadata.csv')):
            data_dir = candidate
            break

    if not data_dir:
        raise FileNotFoundError(f"Dossier introuvable : {possible_data_dirs}")

    csv_path = os.path.join(data_dir, 'annotations_metadata.csv')
    train_dir = os.path.join(data_dir, 'sampled_train')
    
    print(f"Chargement : {data_dir}")
    df = pd.read_csv(csv_path)
    label_map = dict(zip(df['file_id'], df['label']))
    
    texts = []
    labels = []
    
    # 1 pour noHate, -1 pour hate
    for fname in os.listdir(train_dir):
        if fname.endswith('.txt'):
            fid = fname[:-4]
            lbl = label_map.get(fid)
            if lbl in ('hate', 'noHate'):
                fpath = os.path.join(train_dir, fname)
                with open(fpath, 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read().strip()
                if content:
                    texts.append(content)
                    labels.append(1 if lbl == 'noHate' else -1)

    print(f"{len(texts)} textes charges")

    # Decoupage train / test (85% / 15%)
    combined = list(zip(texts, labels))
    random.seed(42)
    random.shuffle(combined)
    split_idx = int(len(combined) * 0.85)
    train_texts, train_labels = zip(*combined[:split_idx])
    test_texts, test_labels = zip(*combined[split_idx:])

    # Frequence documentaire et IDF
    N = len(train_texts)
    doc_freq = Counter()
    for text in train_texts:
        doc_freq.update(set(tokenize(text)))

    vocab = {w: count for w, count in doc_freq.items() if count >= 2}
    idf = {w: math.log((N + 1) / (doc_freq[w] + 1)) + 1.0 for w in vocab}

    def text_to_tfidf(text):
        tokens = tokenize(text)
        if not tokens:
            return {}
        tf = Counter(tokens)
        total = len(tokens)
        return {w: (count / total) * idf[w] for w, count in tf.items() if w in vocab}

    # Apprentissage du perceptron
    weights = defaultdict(float)
    b = 0.0
    eta = 0.05
    epochs = 25

    for epoch in range(epochs):
        errors = 0
        for text, y in zip(train_texts, train_labels):
            x = text_to_tfidf(text)
            score = sum(weights[w] * val for w, val in x.items()) + b
            pred = 1 if score >= 0 else -1
            
            if pred != y:
                errors += 1
                for w, val in x.items():
                    weights[w] += eta * y * val
                b += eta * y
        if (epoch + 1) % 5 == 0:
            print(f"epoch {epoch+1}/{epochs} - erreurs: {errors}/{N}")

    # Evaluation
    correct = 0
    for text, y in zip(test_texts, test_labels):
        tokens = set(tokenize(text))
        score = sum(weights.get(w, 0.0) for w in tokens) + b
        pred = 1 if score >= 0 else -1
        if pred == y:
            correct += 1

    acc = (correct / len(test_texts)) * 100
    print(f"Test accuracy : {acc:.2f}% ({correct}/{len(test_texts)})")

    clean_weights = {w: round(wt, 4) for w, wt in weights.items() if abs(wt) > 1e-4}

    # Sauvegarde des poids pour PHP
    target_dirs = [
        os.path.abspath(os.path.join(base_dir, '..', '..', 'data')),
        os.path.abspath(os.path.join(base_dir, '..', 'data'))
    ]

    data = {
        'bias': round(b, 4),
        'weights': clean_weights
    }

    for out_dir in target_dirs:
        os.makedirs(out_dir, exist_ok=True)
        out_file = os.path.join(out_dir, 'model_weights.json')
        with open(out_file, 'w', encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False, separators=(',', ':'))
        print(f"Export : {out_file}")

if __name__ == '__main__':
    train_and_export()
