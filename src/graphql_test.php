<?php
session_start();
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GraphQL API Test - Profesorji Komentarji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .graphql-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .query-input {
            font-family: 'Courier New', monospace;
            background: #2d3748;
            color: #e2e8f0;
            border: none;
            border-radius: 5px;
        }
        .result-container {
            background: #2d3748;
            color: #e2e8f0;
            border-radius: 5px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            max-height: 400px;
            overflow-y: auto;
        }
        .example-queries {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <!-- Navigacija -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap me-2"></i>
                Profesorji Komentarji
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>Domov
                </a>
                <a class="nav-link" href="quiz.php">
                    <i class="fas fa-question-circle me-1"></i>Kviz
                </a>
                <a class="nav-link active" href="graphql_test.php">
                    <i class="fas fa-code me-1"></i>GraphQL API
                </a>
                <a class="nav-link" href="profesorji_graphql.php">
                    <i class="fas fa-users me-1"></i>Profesorji (GraphQL)
                </a>
                <a class="nav-link" href="/api/docs/">
                    <i class="fas fa-book me-1"></i>API Dokumentacija
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0">
                            <i class="fas fa-code me-2"></i>GraphQL API Test
                        </h2>
                    </div>
                    <div class="card-body">
                        <p class="lead">
                            Testirajte GraphQL API za vašo aplikacijo profesorjev in komentarjev.
                        </p>
                        
                        <!-- Primeri poizvedb -->
                        <div class="example-queries">
                            <h5><i class="fas fa-lightbulb me-2"></i>Primeri poizvedb:</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Vsi profesorji:</h6>
                                    <pre class="bg-light p-2 rounded"><code>query {
  profesorji {
    id
    ime
    url
    komentarji {
      komentar
      created_at
    }
  }
}</code></pre>
                                </div>
                                <div class="col-md-6">
                                    <h6>Statistika kvizov:</h6>
                                    <pre class="bg-light p-2 rounded"><code>query {
  statistika {
    skupno_kvizov
    povprecje
    najboljsi_rezultat
  }
}</code></pre>
                                </div>
                            </div>
                        </div>

                        <!-- GraphQL Playground -->
                        <div class="graphql-container">
                            <h4><i class="fas fa-play me-2"></i>GraphQL Playground</h4>
                            
                            <div class="mb-3">
                                <label for="queryInput" class="form-label">GraphQL poizvedba:</label>
                                <textarea id="queryInput" class="form-control query-input" rows="8" placeholder="Vnesite GraphQL poizvedbo...">query {
  profesorji(limit: 5) {
    id
    ime
    komentarji {
      komentar
      created_at
    }
  }
}</textarea>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-primary" onclick="executeQuery()">
                                    <i class="fas fa-play me-2"></i>Izvedi poizvedbo
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="clearResult()">
                                    <i class="fas fa-trash me-2"></i>Počisti rezultat
                                </button>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Rezultat:</label>
                                <div id="resultContainer" class="result-container" style="display: none;">
                                    <pre id="resultContent"></pre>
                                </div>
                            </div>
                        </div>

                        <!-- Dokumentacija -->
                        <div class="card mt-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-book me-2"></i>Dokumentacija API-ja
                                </h5>
                            </div>
                            <div class="card-body">
                                <h6>Dostopne poizvedbe:</h6>
                                <ul>
                                    <li><strong>profesorji</strong> - Pridobi seznam vseh profesorjev</li>
                                    <li><strong>profesor(id: Int!)</strong> - Pridobi določenega profesorja</li>
                                    <li><strong>komentarji</strong> - Pridobi komentarje (opcijsko filtrirane po profesorju)</li>
                                    <li><strong>kvizRezultati</strong> - Pridobi rezultate kvizov</li>
                                    <li><strong>statistika</strong> - Pridobi statistiko kvizov</li>
                                </ul>
                                
                                <h6>Parametri:</h6>
                                <ul>
                                    <li><strong>limit</strong> - Omeji število rezultatov</li>
                                    <li><strong>offset</strong> - Začni od določenega indeksa</li>
                                    <li><strong>search</strong> - Išči po imenu profesorja</li>
                                    <li><strong>profesor_id</strong> - Filtriraj komentarje po profesorju</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function executeQuery() {
            const query = document.getElementById('queryInput').value.trim();
            if (!query) {
                alert('Vnesite GraphQL poizvedbo!');
                return;
            }

            try {
                const response = await fetch('graphql_simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        query: query
                    })
                });

                const result = await response.json();
                
                // Prikaži rezultat
                document.getElementById('resultContent').textContent = JSON.stringify(result, null, 2);
                document.getElementById('resultContainer').style.display = 'block';
                
            } catch (error) {
                console.error('Napaka:', error);
                document.getElementById('resultContent').textContent = 'Napaka pri izvajanju poizvedbe: ' + error.message;
                document.getElementById('resultContainer').style.display = 'block';
            }
        }

        function clearResult() {
            document.getElementById('resultContainer').style.display = 'none';
            document.getElementById('resultContent').textContent = '';
        }

        // Dodaj poizvedbo v textarea ob kliku na primer
        document.addEventListener('click', function(e) {
            if (e.target.tagName === 'CODE') {
                const query = e.target.textContent;
                document.getElementById('queryInput').value = query;
            }
        });
    </script>
</body>
</html>
