<?php
// Test stran za preverjanje GraphQL endpoint-a
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test GraphQL Endpoint</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .result { background: #f5f5f5; padding: 10px; border-radius: 3px; margin: 10px 0; }
        .error { background: #ffebee; color: #c62828; }
        .success { background: #e8f5e8; color: #2e7d32; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
    <h1>Test GraphQL Endpoint</h1>
    
    <div class="test-section">
        <h3>Test 1: Osnovna poizvedba profesorjev</h3>
        <button onclick="testBasicQuery()">Testiraj</button>
        <div id="result1" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>Test 2: Poizvedba s komentarji</h3>
        <button onclick="testCommentsQuery()">Testiraj</button>
        <div id="result2" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>Test 3: Statistika</h3>
        <button onclick="testStatisticsQuery()">Testiraj</button>
        <div id="result3" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>Test 4: Debug informacije</h3>
        <button onclick="testDebugQuery()">Testiraj</button>
        <div id="result4" class="result"></div>
    </div>

    <script>
        async function testGraphQL(query, resultId) {
            const resultDiv = document.getElementById(resultId);
            resultDiv.innerHTML = 'Nalaganje...';
            
            try {
                const response = await fetch('graphql_simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ query })
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.errors) {
                    resultDiv.innerHTML = `<div class="error">Napaka: ${result.errors[0].message}</div>`;
                } else {
                    resultDiv.innerHTML = `<div class="success">Uspešno!</div><pre>${JSON.stringify(result, null, 2)}</pre>`;
                }
                
            } catch (error) {
                resultDiv.innerHTML = `<div class="error">Napaka: ${error.message}</div>`;
            }
        }
        
        function testBasicQuery() {
            const query = `
                query {
                    profesorji {
                        id
                        ime
                    }
                }
            `;
            testGraphQL(query, 'result1');
        }
        
        function testCommentsQuery() {
            const query = `
                query {
                    profesorji {
                        id
                        ime
                        komentarji {
                            komentar
                        }
                    }
                }
            `;
            testGraphQL(query, 'result2');
        }
        
        function testStatisticsQuery() {
            const query = `
                query {
                    statistika {
                        skupno_kvizov
                        povprecje
                    }
                }
            `;
            testGraphQL(query, 'result3');
        }
        
        function testDebugQuery() {
            const query = `
                query {
                    profesorji {
                        id
                        ime
                    }
                }
            `;
            testGraphQL(query + '&debug=1', 'result4');
        }
    </script>
</body>
</html>
