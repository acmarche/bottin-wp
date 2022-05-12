##Explications


https://jolicode.com/blog/construire-un-bon-analyzer-francais-pour-elasticsearch

https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-lang-analyzer.html#french-analyzer

**char_filter** permet d’appliquer des transformations sur le texte complet, avant qu’il ne soit découpé en tokens. Cette étape permet de nettoyer le contenu, remplacer certains raccourcis, enlever du HTML ou de la ponctuation mal venue, etc.

**tokenizer** consiste à couper le texte en tokens. Elasticsearch utilise par défaut le standard Unicode Text Segmentation, qui va retirer la ponctuation et couper à chaque espace.

**token Filter** C'est là que la majorité du travail de nettoyage et d’enrichissement s’effectue lors de l’analyse. Les token_filter peuvent modifier, ajouter et supprimer des tokens – leur rôle est donc multiple et leur ordre d’exécution important : il s’agit d’une chaîne de filtres.

---

**french_elision**: il enlève les articles pouvant précéder un mot, et donc d’origine devient origine.

**french_stop**:  clause match, il n’est plus nécessaire d’utiliser ce filtre

**french_stemmer**: permet de supprimer les formes plurielles, les différentes conjugaisons, accord de genre sur un mot
Il existe trois algorithmes pour le français, mais nous conserverons le light_french utilisé par défaut.

**icu_tokenizer** :
- normaliser nos textes pour s’assurer que toutes les variantes d’une lettre soient simplifiées;
- remplacer les lettres accentuées par leurs formes sans accents
bœuf => boeuf et ç => c

**2stemmers**
nous allons pouvoir trouver le mot « composé » en recherchant « composer »
- **french_heavy**: qui va faire une analyse poussée, qui va fortement altérer les tokens mais qui va être très utile pour la collecte (nous aurons beaucoup de résultats) :
hamburg
- **french_light**: qui altère le moins possible le contenu et va nous permettre d’augmenter la pertinence de nos résultats
:hamburger

sudo bin/elasticsearch-plugin install analysis-icu

