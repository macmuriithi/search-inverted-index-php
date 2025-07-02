# PHP Inverted Index 🔍

A simple yet powerful inverted index implementation in PHP with TF-IDF scoring for document search and retrieval. Perfect for understanding search engine fundamentals or building lightweight search functionality.

## Features ✨

- **Full-text indexing** with automatic document processing
- **TF-IDF scoring** for relevance ranking
- **Snippet generation** with query term highlighting
- **Stop word filtering** for improved search quality
- **Web interface** for interactive testing
- **JSON export/import** for index persistence
- **Position tracking** for future phrase search capabilities

## Quick Start 🚀

### CLI Demo
```bash
php search.php
```

### Web Interface
1. Place the file in your web server directory
2. Access via browser: `http://localhost/search.php`
3. Add documents and search interactively

### Programmatic Usage
```php
$index = new InvertedIndex();

// Add documents
$docId = $index->addDocument("PHP is great for web development", "PHP Tutorial");

// Search
$results = $index->search("PHP web");
foreach ($results as $result) {
    echo $result['title'] . " (Score: " . $result['score'] . ")\n";
}
```

## How It Works 🧠

### 🔄 INDEXING PROCESS

#### Step 1: Document Reception
```php
// Input: Document content + optional title
$docId = $index->addDocument("PHP programming is fun", "PHP Basics");
// Assigns unique ID and stores metadata
```

#### Step 2: Text Tokenization
```
Raw text: "PHP is a Popular Programming Language!"
↓ Lowercase: "php is a popular programming language!"
↓ Remove punctuation: "php is a popular programming language"
↓ Split words: ["php", "is", "a", "popular", "programming", "language"]
↓ Remove stop words: ["php", "popular", "programming", "language"]
```

#### Step 3: Term Frequency Calculation
```php
// Count occurrences of each term
$termFreq = [
    "php" => 1,
    "popular" => 1,
    "programming" => 1,
    "language" => 1
];
```

#### Step 4: Position Tracking
```php
// Record where each term appears (for snippets)
$positions = [
    "php" => [0],
    "popular" => [1],
    "programming" => [2],
    "language" => [3]
];
```

#### Step 5: Inverted Index Construction
```php
// Build term → documents mapping
$index = [
    "php" => [
        1 => ["frequency" => 1, "positions" => [0]],
        3 => ["frequency" => 2, "positions" => [0, 5]]
    ],
    "programming" => [
        1 => ["frequency" => 1, "positions" => [2]],
        2 => ["frequency" => 1, "positions" => [1]]
    ]
];
```

### 🎯 SCORING PROCESS (TF-IDF)

#### Step 1: Term Frequency (TF)
```
Formula: TF = (term count in document) / (total words in document)

Example:
Document: "PHP programming PHP development" (4 words)
TF("PHP") = 2/4 = 0.5
```

#### Step 2: Inverse Document Frequency (IDF)
```
Formula: IDF = log(total documents / documents containing term)

Example:
Total documents: 100
Documents with "PHP": 10
IDF("PHP") = log(100/10) = 2.3
```

#### Step 3: TF-IDF Score
```
Formula: Score = TF × IDF

Example:
TF-IDF("PHP") = 0.5 × 2.3 = 1.15
```

### 🔍 SEARCH PROCESS

#### Step 1: Query Preprocessing
```php
// Same tokenization as indexing
$query = "PHP Programming!";
$tokens = ["php", "programming"];
```

#### Step 2: Term Lookup
```php
// Find documents containing each term
"php" found in: [doc_1, doc_3, doc_5]
"programming" found in: [doc_1, doc_2, doc_4]
```

#### Step 3: Score Calculation
```php
// For each document, sum TF-IDF scores for all query terms
Document 1:
- "php": TF=0.2, IDF=2.3 → Score: 0.46
- "programming": TF=0.1, IDF=0.69 → Score: 0.069
Total: 0.529
```

#### Step 4: Ranking & Results
```php
// Sort by relevance score (highest first)
[
    ["title" => "Advanced PHP", "score" => 0.847],
    ["title" => "PHP Basics", "score" => 0.529],
    ["title" => "Web Dev Guide", "score" => 0.231]
]
```

## API Reference 📖

### InvertedIndex Class

#### Methods

**`addDocument(string $content, string $title = ''): int`**
- Adds a document to the index
- Returns document ID

**`search(string $query): array`**
- Searches for documents matching the query
- Returns array of results with scores and snippets

**`getStats(): array`**
- Returns index statistics (document count, terms, etc.)

**`exportIndex(): string`**
- Exports index to JSON for persistence

**`importIndex(string $jsonData): bool`**
- Imports index from JSON

### Search Result Structure
```php
[
    'document_id' => 1,
    'title' => 'Document Title',
    'content' => 'Full document content...',
    'score' => 0.847,
    'snippet' => 'Highlighted snippet with <strong>query terms</strong>...'
]
```

## Configuration Options ⚙️

### Stop Words
Default stop words can be modified in the `tokenize()` method:
```php
$stopWords = ['the', 'a', 'an', 'and', 'or', 'but', ...];
```

### Snippet Length
Adjust snippet size by modifying:
```php
$snippetLength = 30; // words
```

### Tokenization Rules
Customize text processing in the `tokenize()` method:
```php
// Current: removes punctuation, converts to lowercase
$text = strtolower($text);
$text = preg_replace('/[^\w\s]/', ' ', $text);
```

## Advanced Features 🔧

### Index Persistence
```php
// Save index
$jsonData = $index->exportIndex();
file_put_contents('search_index.json', $jsonData);

// Load index
$jsonData = file_get_contents('search_index.json');
$index->importIndex($jsonData);
```

### Batch Document Processing
```php
$documents = [
    ['title' => 'Doc 1', 'content' => 'Content 1...'],
    ['title' => 'Doc 2', 'content' => 'Content 2...']
];

foreach ($documents as $doc) {
    $index->addDocument($doc['content'], $doc['title']);
}
```

## Performance Characteristics 📊

### Time Complexity
- **Indexing**: O(n × m) - n documents, m average length
- **Searching**: O(t × d) - t query terms, d documents per term

### Space Complexity
- **Index Size**: O(v × d) - v vocabulary size, d documents per term
- **Memory Usage**: Approximately 50-100MB per 10,000 documents

### Optimization Tips
1. **Pre-process documents** in batches for better performance
2. **Use stemming** to reduce vocabulary size
3. **Implement caching** for frequent queries
4. **Consider database storage** for large document collections

## Algorithm Deep Dive 🎓

### Why TF-IDF Works
- **Term Frequency (TF)**: Rewards documents where query terms appear frequently
- **Inverse Document Frequency (IDF)**: Penalizes common words, rewards rare terms
- **Balance**: Prevents common words from dominating search results

### Scoring Example
```
Query: "PHP tutorial"
Document A: "PHP tutorial for beginners. PHP is easy to learn."
Document B: "Tutorial on various programming languages including PHP."

Document A:
- "PHP": TF=2/10=0.2, IDF=log(100/20)=1.6 → 0.32
- "tutorial": TF=1/10=0.1, IDF=log(100/50)=0.69 → 0.069
- Total: 0.389

Document B:  
- "PHP": TF=1/8=0.125, IDF=1.6 → 0.2
- "tutorial": TF=1/8=0.125, IDF=0.69 → 0.086
- Total: 0.286

Result: Document A ranks higher (more focused on PHP)
```

## Requirements 📋

- PHP 7.0+
- Web server (for web interface)
- JSON extension (usually included)

## Installation 💻

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/php-inverted-index.git
   cd php-inverted-index
   ```

2. **Run CLI demo**
   ```bash
   php search.php
   ```

3. **Set up web interface**
   ```bash
   # Copy to web directory
   cp search.php /var/www/html/
   # Access via browser
   open http://localhost/search.php
   ```

## Examples 💡

### Building a Document Search System
```php
$index = new InvertedIndex();

// Add documents from files
$files = glob('documents/*.txt');
foreach ($files as $file) {
    $content = file_get_contents($file);
    $title = basename($file, '.txt');
    $index->addDocument($content, $title);
}

// Search and display results
$results = $index->search("machine learning algorithms");
foreach ($results as $result) {
    echo "📄 {$result['title']} (Score: {$result['score']})\n";
    echo "   {$result['snippet']}\n\n";
}
```

### Building a FAQ Search
```php
$faqs = [
    "How do I reset my password?" => "To reset your password, click the forgot password link...",
    "What payment methods do you accept?" => "We accept credit cards, PayPal, and bank transfers...",
    "How can I contact support?" => "You can reach our support team via email or phone..."
];

$index = new InvertedIndex();
foreach ($faqs as $question => $answer) {
    $index->addDocument($answer, $question);
}

// User searches for help
$userQuery = "payment credit card";
$results = $index->search($userQuery);
```

## Contributing 🤝

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Roadmap 🗺️

- [ ] **Stemming support** for better term matching
- [ ] **Phrase search** using position data
- [ ] **Fuzzy matching** for typo tolerance
- [ ] **Boolean queries** (AND, OR, NOT operators)
- [ ] **Field-specific search** (title, content, tags)
- [ ] **Database backend** for large-scale deployment
- [ ] **REST API** for external integration

## License 📄

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments 🙏

- Inspired by classic information retrieval algorithms
- TF-IDF scoring based on academic research in search engines
- Built for educational purposes and practical applications

---

**Happy Searching!** 🔍✨

For questions or support, please open an issue or contact the maintainers.
