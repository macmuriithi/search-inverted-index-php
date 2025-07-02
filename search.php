<?php

class InvertedIndex {
    private $index = [];
    private $documents = [];
    private $documentCount = 0;
    
    /**
     * Add a document to the index
     * @param string $content The document content
     * @param string $title Optional document title
     * @return int Document ID
     */
    public function addDocument($content, $title = '') {
        $docId = ++$this->documentCount;
        
        // Store the document
        $this->documents[$docId] = [
            'title' => $title ?: "Document $docId",
            'content' => $content,
            'length' => str_word_count($content)
        ];
        
        // Tokenize and index the document
        $tokens = $this->tokenize($content);
        $termFrequency = array_count_values($tokens);
        
        foreach ($termFrequency as $term => $frequency) {
            if (!isset($this->index[$term])) {
                $this->index[$term] = [];
            }
            
            $this->index[$term][$docId] = [
                'frequency' => $frequency,
                'positions' => $this->getTermPositions($tokens, $term)
            ];
        }
        
        return $docId;
    }
    
    /**
     * Tokenize text into searchable terms
     * @param string $text
     * @return array
     */
    private function tokenize($text) {
        // Convert to lowercase and remove punctuation
        $text = strtolower($text);
        $text = preg_replace('/[^\w\s]/', ' ', $text);
        
        // Split into words and remove empty strings
        $words = array_filter(explode(' ', $text), function($word) {
            return strlen(trim($word)) > 0;
        });
        
        // Remove common stop words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should'];
        
        return array_filter($words, function($word) use ($stopWords) {
            return !in_array(trim($word), $stopWords) && strlen(trim($word)) > 1;
        });
    }
    
    /**
     * Get positions of a term in the token array
     * @param array $tokens
     * @param string $term
     * @return array
     */
    private function getTermPositions($tokens, $term) {
        $positions = [];
        foreach ($tokens as $position => $token) {
            if ($token === $term) {
                $positions[] = $position;
            }
        }
        return $positions;
    }
    
    /**
     * Search for documents containing the query terms
     * @param string $query
     * @return array
     */
    public function search($query) {
        $queryTerms = $this->tokenize($query);
        
        if (empty($queryTerms)) {
            return [];
        }
        
        $results = [];
        $documentScores = [];
        
        // Find documents containing query terms
        foreach ($queryTerms as $term) {
            if (isset($this->index[$term])) {
                foreach ($this->index[$term] as $docId => $termData) {
                    if (!isset($documentScores[$docId])) {
                        $documentScores[$docId] = 0;
                    }
                    
                    // Simple TF-IDF scoring
                    $tf = $termData['frequency'] / $this->documents[$docId]['length'];
                    $idf = log($this->documentCount / count($this->index[$term]));
                    $documentScores[$docId] += $tf * $idf;
                }
            }
        }
        
        // Sort by relevance score (highest first)
        arsort($documentScores);
        
        // Format results
        foreach ($documentScores as $docId => $score) {
            $results[] = [
                'document_id' => $docId,
                'title' => $this->documents[$docId]['title'],
                'content' => $this->documents[$docId]['content'],
                'score' => round($score, 4),
                'snippet' => $this->generateSnippet($this->documents[$docId]['content'], $queryTerms)
            ];
        }
        
        return $results;
    }
    
    /**
     * Generate a snippet showing query terms in context
     * @param string $content
     * @param array $queryTerms
     * @return string
     */
    private function generateSnippet($content, $queryTerms) {
        $words = explode(' ', $content);
        $snippet = '';
        $snippetLength = 30; // words
        
        // Find first occurrence of any query term
        $startPos = 0;
        foreach ($words as $pos => $word) {
            $cleanWord = strtolower(preg_replace('/[^\w]/', '', $word));
            if (in_array($cleanWord, $queryTerms)) {
                $startPos = max(0, $pos - 10);
                break;
            }
        }
        
        // Extract snippet
        $snippetWords = array_slice($words, $startPos, $snippetLength);
        $snippet = implode(' ', $snippetWords);
        
        // Highlight query terms
        foreach ($queryTerms as $term) {
            $pattern = '/\b' . preg_quote($term, '/') . '\b/i';
            $snippet = preg_replace($pattern, '<strong>$0</strong>', $snippet);
        }
        
        return $snippet . ($startPos + $snippetLength < count($words) ? '...' : '');
    }
    
    /**
     * Get statistics about the index
     * @return array
     */
    public function getStats() {
        return [
            'total_documents' => $this->documentCount,
            'total_terms' => count($this->index),
            'average_document_length' => $this->documentCount > 0 ? 
                array_sum(array_column($this->documents, 'length')) / $this->documentCount : 0
        ];
    }
    
    /**
     * Export index to JSON for persistence
     * @return string
     */
    public function exportIndex() {
        return json_encode([
            'index' => $this->index,
            'documents' => $this->documents,
            'documentCount' => $this->documentCount
        ]);
    }
    
    /**
     * Import index from JSON
     * @param string $jsonData
     * @return bool
     */
    public function importIndex($jsonData) {
        $data = json_decode($jsonData, true);
        if ($data && isset($data['index']) && isset($data['documents'])) {
            $this->index = $data['index'];
            $this->documents = $data['documents'];
            $this->documentCount = $data['documentCount'];
            return true;
        }
        return false;
    }
}

// Example usage and demo
function runDemo() {
    echo "<h1>Simple Inverted Index Demo</h1>\n";
    
    // Create index
    $index = new InvertedIndex();
    
    // Add sample documents
    $documents = [
        "PHP is a popular programming language for web development. It's easy to learn and widely used.",
        "Python is another programming language known for its simplicity and readability. Great for beginners.",
        "JavaScript is essential for web development, running both in browsers and on servers with Node.js.",
        "Web development involves creating websites and web applications using various technologies like HTML, CSS, and JavaScript.",
        "Database management is crucial for storing and retrieving data in web applications. MySQL and PostgreSQL are popular choices."
    ];
    
    $titles = [
        "Introduction to PHP",
        "Python Programming Basics", 
        "JavaScript Fundamentals",
        "Web Development Overview",
        "Database Management Systems"
    ];
    
    echo "<h2>Adding Documents to Index...</h2>\n";
    foreach ($documents as $i => $doc) {
        $docId = $index->addDocument($doc, $titles[$i]);
        echo "<p>Added: <strong>{$titles[$i]}</strong> (ID: $docId)</p>\n";
    }
    
    // Show index statistics
    $stats = $index->getStats();
    echo "<h2>Index Statistics</h2>\n";
    echo "<ul>\n";
    echo "<li>Total Documents: {$stats['total_documents']}</li>\n";
    echo "<li>Total Terms: {$stats['total_terms']}</li>\n";
    echo "<li>Average Document Length: " . round($stats['average_document_length'], 2) . " words</li>\n";
    echo "</ul>\n";
    
    // Perform searches
    $queries = ["programming", "web development", "JavaScript Node", "database"];
    
    echo "<h2>Search Results</h2>\n";
    foreach ($queries as $query) {
        echo "<h3>Query: \"$query\"</h3>\n";
        $results = $index->search($query);
        
        if (empty($results)) {
            echo "<p>No results found.</p>\n";
        } else {
            echo "<div style='margin-left: 20px;'>\n";
            foreach ($results as $result) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>\n";
                echo "<h4>{$result['title']} (Score: {$result['score']})</h4>\n";
                echo "<p>{$result['snippet']}</p>\n";
                echo "</div>\n";
            }
            echo "</div>\n";
        }
    }
}

// Simple web interface
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    session_start();
    
    if ($_POST['action'] === 'add_document') {
        if (!isset($_SESSION['index_data'])) {
            $_SESSION['index'] = new InvertedIndex();
        } else {
            $_SESSION['index'] = new InvertedIndex();
            $_SESSION['index']->importIndex($_SESSION['index_data']);
        }
        
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        
        if (!empty($content)) {
            $docId = $_SESSION['index']->addDocument($content, $title);
            $_SESSION['index_data'] = $_SESSION['index']->exportIndex();
            echo json_encode(['success' => true, 'document_id' => $docId]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Content cannot be empty']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'search') {
        if (!isset($_SESSION['index_data'])) {
            echo json_encode(['success' => false, 'error' => 'No documents indexed']);
            exit;
        }
        
        $index = new InvertedIndex();
        $index->importIndex($_SESSION['index_data']);
        
        $query = $_POST['query'] ?? '';
        $results = $index->search($query);
        
        echo json_encode(['success' => true, 'results' => $results]);
        exit;
    }
}

// If running in CLI mode, run the demo
if (php_sapi_name() === 'cli') {
    runDemo();
} else {
    // Web interface
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>PHP Inverted Index Demo</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .container { max-width: 800px; margin: 0 auto; }
            .form-group { margin: 10px 0; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input[type="text"], textarea { width: 100%; padding: 8px; box-sizing: border-box; }
            button { padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer; }
            button:hover { background: #005a87; }
            .result { border: 1px solid #ddd; padding: 15px; margin: 10px 0; }
            .snippet { color: #666; }
            .score { float: right; color: #999; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>PHP Inverted Index Demo</h1>
            
            <h2>Add Document</h2>
            <form id="addForm">
                <div class="form-group">
                    <label>Title:</label>
                    <input type="text" id="title" name="title">
                </div>
                <div class="form-group">
                    <label>Content:</label>
                    <textarea id="content" name="content" rows="4" required></textarea>
                </div>
                <button type="submit">Add Document</button>
            </form>
            
            <h2>Search</h2>
            <form id="searchForm">
                <div class="form-group">
                    <label>Search Query:</label>
                    <input type="text" id="query" name="query" required>
                </div>
                <button type="submit">Search</button>
            </form>
            
            <div id="results"></div>
        </div>
        
        <script>
            document.getElementById('addForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData();
                formData.append('action', 'add_document');
                formData.append('title', document.getElementById('title').value);
                formData.append('content', document.getElementById('content').value);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Document added successfully!');
                        document.getElementById('addForm').reset();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            });
            
            document.getElementById('searchForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData();
                formData.append('action', 'search');
                formData.append('query', document.getElementById('query').value);
                
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const resultsDiv = document.getElementById('results');
                    if (data.success) {
                        if (data.results.length === 0) {
                            resultsDiv.innerHTML = '<p>No results found.</p>';
                        } else {
                            let html = '<h3>Search Results</h3>';
                            data.results.forEach(result => {
                                html += `
                                    <div class="result">
                                        <h4>${result.title} <span class="score">Score: ${result.score}</span></h4>
                                        <div class="snippet">${result.snippet}</div>
                                    </div>
                                `;
                            });
                            resultsDiv.innerHTML = html;
                        }
                    } else {
                        resultsDiv.innerHTML = '<p>Error: ' + data.error + '</p>';
                    }
                });
            });
        </script>
    </body>
    </html>
    <?php
}
?>
