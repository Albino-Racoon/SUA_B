<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GraphQL API Dokumentacija - Profesorji Komentarji</title>
    
    <!-- Swagger UI CSS -->
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css" />
    
    <style>
        .swagger-ui .topbar {
            background-color: #6f42c1;
        }
        .swagger-ui .topbar .download-url-wrapper .select-label {
            color: white;
        }
        .swagger-ui .topbar .download-url-wrapper input[type=text] {
            color: #333;
        }
        .swagger-ui .info .title {
            color: #6f42c1;
        }
        .custom-header {
            background: linear-gradient(135deg, #6f42c1 0%, #007bff 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .custom-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .custom-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        .graphql-examples {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 8px;
            margin: 2rem 0;
        }
        .example-query {
            background: #2d3748;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 6px;
            margin: 1rem 0;
            font-family: 'Courier New', monospace;
        }
        .example-response {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            padding: 1rem;
            border-radius: 6px;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <!-- Custom Header -->
    <div class="custom-header">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <h1><i class="fas fa-code me-3"></i>GraphQL API Dokumentacija</h1>
                    <p>Interaktivna dokumentacija za GraphQL API sistema Profesorji Komentarji</p>
                    <p class="mb-0">Sistem omogoča pridobivanje podatkov o profesorjih, komentarjih, kvizih in statistiki</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="mt-3">
                        <span class="badge bg-light text-dark me-2">GraphQL</span>
                        <span class="badge bg-success">v1.0.0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Quick Examples -->
        <div class="graphql-examples">
            <h3><i class="fas fa-rocket me-2"></i>Hitri primeri</h3>
            <div class="row">
                <div class="col-md-6">
                    <h5>Pridobi vse profesorje:</h5>
                    <div class="example-query">
query {
  profesorji(limit: 5) {
    id
    ime
    komentarji {
      komentar
    }
  }
}
                    </div>
                </div>
                <div class="col-md-6">
                    <h5>Išči profesorje:</h5>
                    <div class="example-query">
query {
  profesorji(search: "tina", limit: 3) {
    id
    ime
    komentarji {
      komentar
      created_at
    }
  }
}
                    </div>
                </div>
            </div>
        </div>

        <!-- Swagger UI Container -->
        <div id="swagger-ui">
            <div style="padding: 20px; text-align: center; color: #666;">
                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                <p>Nalaganje Swagger UI...</p>
            </div>
        </div>
        
        <!-- Debug info -->
        <div id="debug-info" style="background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; display: none;">
            <h5>Debug informacije:</h5>
            <div id="debug-content"></div>
        </div>
    </div>

    <!-- Swagger UI JS -->
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js"></script>
    
    <!-- Alternativna verzija Swagger UI -->
    <script>
        // Fallback, če se glavne skripte ne naložijo
        window.addEventListener('load', function() {
            setTimeout(function() {
                if (typeof SwaggerUIBundle === 'undefined') {
                    console.log('Nalaganje alternativne Swagger UI...');
                    loadAlternativeSwagger();
                }
            }, 2000);
        });
        
        function loadAlternativeSwagger() {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/swagger-ui-dist@4.15.5/swagger-ui-bundle.js';
            script.onload = function() {
                const css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = 'https://cdn.jsdelivr.net/npm/swagger-ui-dist@4.15.5/swagger-ui.css';
                document.head.appendChild(css);
                
                // Inicializiraj alternativno Swagger UI
                initAlternativeSwagger();
            };
            document.head.appendChild(script);
        }
        
        function initAlternativeSwagger() {
            try {
                const ui = SwaggerUIBundle({
                    spec: openApiSpec,
                    dom_id: '#swagger-ui',
                    deepLinking: true,
                    presets: [
                        SwaggerUIBundle.presets.apis
                    ],
                    docExpansion: "list",
                    defaultModelsExpandDepth: 2,
                    defaultModelExpandDepth: 2
                });
                console.log('Alternativna Swagger UI uspešno inicializirana');
            } catch (error) {
                console.error('Napaka pri inicializaciji alternativne Swagger UI:', error);
            }
        }
    </script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script>
        // OpenAPI specifikacija za GraphQL API
        const openApiSpec = {
            openapi: "3.0.0",
            info: {
                title: "Profesorji Komentarji GraphQL API",
                description: "GraphQL API za sistem upravljanja profesorjev, komentarjev in kvizov. Sistem omogoča pridobivanje podatkov o profesorjih, iskanje, upravljanje komentarjev in analizo kvizov.",
                version: "1.0.0",
                contact: {
                    name: "GraphQL API Team",
                    email: "support@profesorji.si"
                },
                license: {
                    name: "MIT",
                    url: "https://opensource.org/licenses/MIT"
                }
            },
            servers: [
                {
                    url: "http://localhost",
                    description: "Lokalni razvojni strežnik"
                }
            ],
            paths: {
                "/graphql_simple.php": {
                    post: {
                        summary: "GraphQL Endpoint",
                        description: "Glavni GraphQL endpoint za vse poizvedbe",
                        tags: ["GraphQL"],
                        requestBody: {
                            required: true,
                            content: {
                                "application/json": {
                                    schema: {
                                        type: "object",
                                        properties: {
                                            query: {
                                                type: "string",
                                                description: "GraphQL poizvedba",
                                                example: "query { profesorji(limit: 5) { id ime } }"
                                            }
                                        },
                                        required: ["query"]
                                    }
                                }
                            }
                        },
                        responses: {
                            "200": {
                                description: "Uspešna poizvedba",
                                content: {
                                    "application/json": {
                                        schema: {
                                            type: "object",
                                            properties: {
                                                data: {
                                                    type: "object",
                                                    description: "Podatki poizvedbe"
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            "500": {
                                description: "Napaka strežnika",
                                content: {
                                    "application/json": {
                                        schema: {
                                            type: "object",
                                            properties: {
                                                errors: {
                                                    type: "array",
                                                    items: {
                                                        type: "object",
                                                        properties: {
                                                            message: { type: "string" }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            },
            components: {
                schemas: {
                    Profesor: {
                        type: "object",
                        properties: {
                            id: { type: "integer", description: "Unikatni identifikator profesorja" },
                            ime: { type: "string", description: "Ime in priimek profesorja" },
                            url: { type: "string", description: "URL profilne strani" },
                            created_at: { type: "string", format: "date-time", description: "Datum dodajanja" },
                            updated_at: { type: "string", format: "date-time", description: "Datum zadnje posodobitve" },
                            komentarji: {
                                type: "array",
                                items: { "$ref": "#/components/schemas/Komentar" },
                                description: "Seznam komentarjev o profesorju"
                            }
                        }
                    },
                    Komentar: {
                        type: "object",
                        properties: {
                            id: { type: "integer", description: "Unikatni identifikator komentarja" },
                            komentar: { type: "string", description: "Besedilo komentarja" },
                            created_at: { type: "string", format: "date-time", description: "Datum objave" },
                            profesor: { "$ref": "#/components/schemas/Profesor", description: "Profesor, na katerega se nanaša komentar" }
                        }
                    },
                    KvizRezultat: {
                        type: "object",
                        properties: {
                            id: { type: "integer", description: "Unikatni identifikator kviza" },
                            uporabnik_id: { type: "string", description: "Identifikator uporabnika" },
                            stevilo_pravilnih: { type: "integer", description: "Število pravilnih odgovorov" },
                            stevilo_vprasanj: { type: "integer", description: "Skupno število vprašanj" },
                            rezultat: { type: "number", format: "float", description: "Rezultat v odstotkih" },
                            created_at: { type: "string", format: "date-time", description: "Datum opravljanja kviza" }
                        }
                    },
                    Statistika: {
                        type: "object",
                        properties: {
                            skupno_kvizov: { type: "integer", description: "Skupno število opravljenih kvizov" },
                            povprecje: { type: "number", format: "float", description: "Povprečen rezultat kvizov" },
                            najboljsi_rezultat: { type: "number", format: "float", description: "Najboljši dosežen rezultat" },
                            najslabsi_rezultat: { type: "number", format: "float", description: "Najslabši dosežen rezultat" },
                            skupno_pravilnih: { type: "integer", description: "Skupno število pravilnih odgovorov" },
                            skupno_vprasanj: { type: "integer", description: "Skupno število vprašanj" }
                        }
                    }
                }
            },
            tags: [
                {
                    name: "GraphQL",
                    description: "Glavni GraphQL endpoint in poizvedbe"
                },
                {
                    name: "Profesorji",
                    description: "Upravljanje podatkov o profesorjih"
                },
                {
                    name: "Komentarji",
                    description: "Upravljanje komentarjev o profesorjih"
                },
                {
                    name: "Kviz",
                    description: "Upravljanje kvizov in rezultatov"
                },
                {
                    name: "Statistika",
                    description: "Analitični podatki in statistike"
                }
            ]
        };

        // Inicializacija Swagger UI
        window.onload = function() {
            try {
                // Preveri, ali so se skripte naložile
                if (typeof SwaggerUIBundle === 'undefined') {
                    console.error('SwaggerUIBundle ni naložen');
                    return;
                }
                
                if (typeof SwaggerUIStandalonePreset === 'undefined') {
                    console.error('SwaggerUIStandalonePreset ni naložen');
                    // Uporabi samo osnovne presets
                    const ui = SwaggerUIBundle({
                        spec: openApiSpec,
                        dom_id: '#swagger-ui',
                        deepLinking: true,
                        presets: [
                            SwaggerUIBundle.presets.apis
                        ],
                        plugins: [
                            SwaggerUIBundle.plugins.DownloadUrl
                        ],
                        docExpansion: "list",
                        defaultModelsExpandDepth: 2,
                        defaultModelExpandDepth: 2,
                        tryItOutEnabled: true
                    });
                } else {
                    // Uporabi vse presets
                    const ui = SwaggerUIBundle({
                        spec: openApiSpec,
                        dom_id: '#swagger-ui',
                        deepLinking: true,
                        presets: [
                            SwaggerUIBundle.presets.apis,
                            SwaggerUIStandalonePreset
                        ],
                        plugins: [
                            SwaggerUIBundle.plugins.DownloadUrl
                        ],
                        layout: "Standalone",
                        docExpansion: "list",
                        defaultModelsExpandDepth: 2,
                        defaultModelExpandDepth: 2,
                        tryItOutEnabled: true
                    });
                }
                
                console.log('Swagger UI uspešno inicializiran');
                
                // Preveri, ali se je Swagger prikazal
                setTimeout(function() {
                    const swaggerContainer = document.querySelector('#swagger-ui');
                    const debugInfo = document.getElementById('debug-info');
                    const debugContent = document.getElementById('debug-content');
                    
                    if (swaggerContainer) {
                        console.log('Swagger container najden:', swaggerContainer);
                        console.log('Swagger HTML:', swaggerContainer.innerHTML.substring(0, 200));
                        
                        // Prikaži debug informacije
                        debugInfo.style.display = 'block';
                        debugContent.innerHTML = `
                            <p><strong>Status:</strong> ✅ Swagger container najden</p>
                            <p><strong>HTML vsebina:</strong> ${swaggerContainer.innerHTML.substring(0, 200)}...</p>
                            <p><strong>Število otrok:</strong> ${swaggerContainer.children.length}</p>
                        `;
                        
                        // Skrij loading spinner
                        const loadingDiv = swaggerContainer.querySelector('div[style*="text-align: center"]');
                        if (loadingDiv) {
                            loadingDiv.style.display = 'none';
                        }
                    } else {
                        console.error('Swagger container ni najden!');
                        debugInfo.style.display = 'block';
                        debugContent.innerHTML = '<p><strong>Status:</strong> ❌ Swagger container ni najden!</p>';
                    }
                }, 1000);
                
            } catch (error) {
                console.error('Napaka pri inicializaciji Swagger UI:', error);
            }
        };

        // Dodaj GraphQL primer v Swagger UI
        document.addEventListener('DOMContentLoaded', function() {
            // Počakaj, da se Swagger UI naloži
            setTimeout(function() {
                const swaggerContainer = document.querySelector('#swagger-ui');
                if (swaggerContainer) {
                    const graphqlSection = document.createElement('div');
                    graphqlSection.className = 'graphql-section';
                    graphqlSection.innerHTML = `
                        <div class="swagger-ui">
                            <div class="opblock opblock-post">
                                <div class="opblock-summary">
                                    <span class="opblock-summary-method">POST</span>
                                    <span class="opblock-summary-path">/graphql_simple.php</span>
                                    <span class="opblock-summary-description">GraphQL Endpoint</span>
                                </div>
                                <div class="opblock-body">
                                    <div class="opblock-section">
                                        <div class="opblock-section-header">
                                            <h4>GraphQL Poizvedbe</h4>
                                        </div>
                                        <div class="opblock-section-body">
                                            <div class="example-query">
                                                <h5>1. Pridobi vse profesorje:</h5>
                                                <pre><code>query {
  profesorji(limit: 5) {
    id
    ime
    komentarji {
      komentar
    }
  }
}</code></pre>
                                            </div>
                                            <div class="example-query">
                                                <h5>2. Išči profesorje:</h5>
                                                <pre><code>query {
  profesorji(search: "tina", limit: 3) {
    id
    ime
    komentarji {
      komentar
      created_at
    }
  }
}</code></pre>
                                            </div>
                                            <div class="example-query">
                                                <h5>3. Pridobi statistiko:</h5>
                                                <pre><code>query {
  statistika {
    skupno_kvizov
    povprecje
    najboljsi_rezultat
  }
}</code></pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Vstavi GraphQL sekcijo na vrh
                    swaggerContainer.insertBefore(graphqlSection, swaggerContainer.firstChild);
                }
            }, 1000);
        });
    </script>
</body>
</html>
