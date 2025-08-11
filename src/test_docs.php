<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Dokumentacije</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #e8f5e8; color: #2e7d32; padding: 10px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>Test Dokumentacije</h1>
    
    <div class="test-section">
        <h3>Preverjanje delovanja</h3>
        <div class="success">
            ✅ Stran se je uspešno naložila!
        </div>
        <p>Če vidite to sporočilo, potem deluje osnovno nalaganje strani.</p>
    </div>
    
    <div class="test-section">
        <h3>Povezave</h3>
        <ul>
            <li><a href="index.php">Domov</a></li>
            <li><a href="api_docs.php">API Dokumentacija</a></li>
            <li><a href="graphql_test.php">GraphQL Test</a></li>
            <li><a href="profesorji_graphql.php">Profesorji GraphQL</a></li>
        </ul>
    </div>
    
    <div class="test-section">
        <h3>Test GraphQL</h3>
        <button onclick="testGraphQL()">Testiraj GraphQL</button>
        <div id="result"></div>
    </div>

    <script>
        async function testGraphQL() {
            const resultDiv = document.getElementById('result');
            resultDiv.innerHTML = 'Testiranje...';
            
            try {
                const response = await fetch('graphql_simple.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        query: 'query { profesorji(limit: 1) { id ime } }'
                    })
                });
                
                if (response.ok) {
                    const result = await response.json();
                    resultDiv.innerHTML = `<div class="success">✅ GraphQL deluje! Rezultat: ${JSON.stringify(result)}</div>`;
                } else {
                    resultDiv.innerHTML = `<div style="color: red;">❌ Napaka: ${response.status}</div>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<div style="color: red;">❌ Napaka: ${error.message}</div>`;
            }
        }
    </script>
</body>
</html>
