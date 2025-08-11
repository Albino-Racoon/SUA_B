<?php
// Test stran za preverjanje iskanja profesorjev
require_once 'models/Profesor.php';

$profesor = new Profesor();
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Iskanja Profesorjev</title>
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
    <h1>Test Iskanja Profesorjev</h1>
    
    <div class="test-section">
        <h3>Test 1: Direktno iskanje v bazi</h3>
        <button onclick="testDirectSearch()">Testiraj direktno iskanje</button>
        <div id="result1" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>Test 2: GraphQL iskanje</h3>
        <button onclick="testGraphQLSearch()">Testiraj GraphQL iskanje</button>
        <div id="result2" class="result"></div>
    </div>
    
    <div class="test-section">
        <h3>Test 3: Vsi profesorji</h3>
        <button onclick="testAllProfessors()">Testiraj vse profesorje</button>
        <div id="result3" class="result"></div>
    </div>

    <script>
        async function testDirectSearch() {
            const resultDiv = document.getElementById('result1');
            resultDiv.innerHTML = 'Nalaganje...';
            
            try {
                const response = await fetch('test_direct_search.php');
                const result = await response.text();
                resultDiv.innerHTML = `<div class="success">Uspešno!</div><pre>${result}</pre>`;
            } catch (error) {
                resultDiv.innerHTML = `<div class="error">Napaka: ${error.message}</div>`;
            }
        }
        
        async function testGraphQLSearch() {
            const resultDiv = document.getElementById('result2');
            resultDiv.innerHTML = 'Nalaganje...';
            
            try {
                const query = `
                    query {
                        profesorji(search: "tina", limit: 5) {
                            id
                            ime
                            komentarji {
                                komentar
                            }
                        }
                    }
                `;
                
                const response = await fetch('graphql_simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ query })
                });
                
                const result = await response.json();
                resultDiv.innerHTML = `<div class="success">Uspešno!</div><pre>${JSON.stringify(result, null, 2)}</pre>`;
            } catch (error) {
                resultDiv.innerHTML = `<div class="error">Napaka: ${error.message}</div>`;
            }
        }
        
        async function testAllProfessors() {
            const resultDiv = document.getElementById('result3');
            resultDiv.innerHTML = 'Nalaganje...';
            
            try {
                const query = `
                    query {
                        profesorji(limit: 3) {
                            id
                            ime
                            komentarji {
                                komentar
                            }
                        }
                    }
                `;
                
                const response = await fetch('graphql_simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ query })
                });
                
                const result = await response.json();
                resultDiv.innerHTML = `<div class="success">Uspešno!</div><pre>${JSON.stringify(result, null, 2)}</pre>`;
            } catch (error) {
                resultDiv.innerHTML = `<div class="error">Napaka: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>
