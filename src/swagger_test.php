<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Swagger Test</title>
    
    <!-- Swagger UI CSS -->
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css" />
    
    <style>
        body { margin: 0; padding: 20px; font-family: Arial, sans-serif; }
        .header { background: #6f42c1; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🧪 Swagger UI Test</h1>
        <p>Preverjanje delovanja Swagger UI</p>
    </div>
    
    <div id="status"></div>
    
    <!-- Swagger UI Container -->
    <div id="swagger-ui"></div>
    
    <!-- Swagger UI JS -->
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js"></script>
    
    <script>
        // Preveri, ali so se skripte naložile
        function checkScripts() {
            const statusDiv = document.getElementById('status');
            
            if (typeof SwaggerUIBundle !== 'undefined') {
                statusDiv.innerHTML = '<div class="status success">✅ SwaggerUIBundle je naložen</div>';
            } else {
                statusDiv.innerHTML = '<div class="status error">❌ SwaggerUIBundle ni naložen</div>';
                return;
            }
            
            if (typeof SwaggerUIStandalonePreset !== 'undefined') {
                statusDiv.innerHTML += '<div class="status success">✅ SwaggerUIStandalonePreset je naložen</div>';
            } else {
                statusDiv.innerHTML += '<div class="status error">❌ SwaggerUIStandalonePreset ni naložen</div>';
                return;
            }
            
            // Inicializiraj Swagger UI
            initSwagger();
        }
        
        function initSwagger() {
            try {
                const ui = SwaggerUIBundle({
                    url: 'https://petstore.swagger.io/v2/swagger.json', // Test API
                    dom_id: '#swagger-ui',
                    deepLinking: true,
                    presets: [
                        SwaggerUIBundle.presets.apis,
                        SwaggerUIStandalonePreset
                    ],
                    plugins: [
                        SwaggerUIBundle.plugins.DownloadUrl
                    ],
                    layout: "Standalone"
                });
                
                document.getElementById('status').innerHTML += '<div class="status success">✅ Swagger UI je inicializiran</div>';
            } catch (error) {
                document.getElementById('status').innerHTML += '<div class="status error">❌ Napaka pri inicializaciji: ' + error.message + '</div>';
            }
        }
        
        // Počakaj, da se DOM naloži
        document.addEventListener('DOMContentLoaded', function() {
            // Počakaj malo, da se skripte naložijo
            setTimeout(checkScripts, 1000);
        });
        
        // Preveri tudi ob window.onload
        window.onload = function() {
            setTimeout(checkScripts, 500);
        };
    </script>
</body>
</html>
