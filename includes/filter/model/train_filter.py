import os
import re
import math
import json
import random
from collections import Counter
import pandas as pd
from scipy.sparse import csr_matrix
from sklearn.linear_model import Perceptron

def tokenize(text):
    return re.findall(r'\b[a-zA-Z]{2,}\b', text.lower())

def train_and_export():
    base_dir = os.path.dirname(os.path.abspath(__file__))
    data_dir = os.path.abspath(os.path.join(base_dir, '..', 'data', 'sampled_train'))

    # Chargement des annotations et des textes
    df = pd.read_csv(os.path.join(data_dir, 'annotations_metadata.csv'))
    label_map = dict(zip(df['file_id'], df['label']))
    train_dir = os.path.join(data_dir, 'sampled_train')

    texts, labels = [], []
    for fname in os.listdir(train_dir):
        if fname.endswith('.txt'):
            lbl = label_map.get(fname[:-4])
            if lbl in ('hate', 'noHate'):
                with open(os.path.join(train_dir, fname), 'r', encoding='utf-8', errors='ignore') as f:
                    content = f.read().strip()
                if content:
                    texts.append(content)
                    labels.append(1 if lbl == 'noHate' else -1)

    combined = list(zip(texts, labels))
    random.seed(42)
    random.shuffle(combined)
    split_idx = int(len(combined) * 0.85)
    train_texts, train_labels = zip(*combined[:split_idx])
    test_texts, test_labels = zip(*combined[split_idx:])

    # Calcul manuel de l'IDF
    N = len(train_texts)
    doc_freq = Counter()
    for text in train_texts:
        doc_freq.update(set(tokenize(text)))

    vocab_list = [w for w, count in doc_freq.items() if count >= 2]
    word_to_idx = {w: i for i, w in enumerate(vocab_list)}
    idf = {w: math.log((N + 1) / (doc_freq[w] + 1)) + 1.0 for w in vocab_list}

    # Vectorisation TF-IDF manuelle
    def build_matrix(text_list):
        rows, cols, data = [], [], []
        for r, text in enumerate(text_list):
            tokens = tokenize(text)
            if not tokens:
                continue
            tf = Counter(tokens)
            total = len(tokens)
            for w, count in tf.items():
                if w in word_to_idx:
                    rows.append(r)
                    cols.append(word_to_idx[w])
                    data.append((count / total) * idf[w])
        return csr_matrix((data, (rows, cols)), shape=(len(text_list), len(vocab_list)))

    X_train = build_matrix(train_texts)
    X_test = build_matrix(test_texts)

    # Entrainement du perceptron via sklearn
    clf = Perceptron(max_iter=1000, random_state=42)
    clf.fit(X_train, train_labels)

    acc = clf.score(X_test, test_labels)
    print(f"Accuracy test: {acc * 100:.2f}%")

    # Export des poids pour PHP
    weights = {w: round(float(coef), 4) for w, coef in zip(vocab_list, clf.coef_[0]) if abs(coef) > 1e-4}
    data = {
        'bias': round(float(clf.intercept_[0]), 4),
        'weights': weights
    }

    # shortcut: ecriture sur deux dossiers pour assurer la disponibilite selon le point d'entree PHP
    target = os.path.abspath(os.path.join(base_dir, '..', 'data', 'model_weights.json'))
    os.makedirs(os.path.dirname(target), exist_ok=True)
    with open(target, 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, separators=(',', ':'))
    print(f"Poids exportes dans {target}")

if __name__ == '__main__':
    train_and_export()
