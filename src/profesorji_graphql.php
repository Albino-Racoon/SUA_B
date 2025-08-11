<?php
session_start();
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profesorji - GraphQL API - Profesorji Komentarji</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .professor-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .professor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .professor-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .comment-item {
            border-left: 3px solid #007bff;
            padding-left: 15px;
            margin-bottom: 10px;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .stats-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
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
                <a class="nav-link" href="graphql_test.php">
                    <i class="fas fa-code me-1"></i>GraphQL API
                </a>
                <a class="nav-link active" href="profesorji_graphql.php">
                    <i class="fas fa-users me-1"></i>Profesorji (GraphQL)
                </a>
                <a class="nav-link" href="/api/docs/">
                    <i class="fas fa-book me-1"></i>API Dokumentacija
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Naslov in opis -->
        <div class="stats-banner text-center">
            <h1 class="mb-3">
                <i class="fas fa-users me-3"></i>Profesorji preko GraphQL API-ja
            </h1>
            <p class="lead mb-0">
                Podatki so pridobljeni dinamično preko GraphQL poizvedb
            </p>
        </div>

        <!-- Filtri in iskanje -->
        <div class="filter-section">
            <div class="row">
                <div class="col-md-4">
                    <label for="searchInput" class="form-label">
                        <i class="fas fa-search me-2"></i>Išči profesorje
                    </label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Vnesite ime...">
                </div>
                <div class="col-md-3">
                    <label for="limitSelect" class="form-label">
                        <i class="fas fa-list me-2"></i>Število rezultatov
                    </label>
                    <select id="limitSelect" class="form-select">
                        <option value="5">5 profesorjev</option>
                        <option value="10" selected>10 profesorjev</option>
                        <option value="20">20 profesorjev</option>
                        <option value="50">50 profesorjev</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sortSelect" class="form-label">
                        <i class="fas fa-sort me-2"></i>Razvrsti po
                    </label>
                    <select id="sortSelect" class="form-select">
                        <option value="ime">Ime (A-Z)</option>
                        <option value="komentarji">Število komentarjev</option>
                        <option value="datum">Datum dodajanja</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary w-100" onclick="loadProfessors()">
                        <i class="fas fa-search me-2"></i>Išči
                    </button>
                </div>
            </div>
        </div>



        <!-- Seznam profesorjev -->
        <div id="professorsContainer">
            <div class="loading">
                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                <p>Nalaganje profesorjev...</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // GraphQL funkcije
        async function executeGraphQLQuery(query) {
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
                    throw new Error(result.errors[0].message);
                }
                
                return result;
            } catch (error) {
                console.error('GraphQL napaka:', error);
                throw error;
            }
        }



        // Naloži profesorje
        async function loadProfessors() {
            const searchTerm = document.getElementById('searchInput').value.trim();
            const limit = document.getElementById('limitSelect').value;
            const sortBy = document.getElementById('sortSelect').value;

            console.log('loadProfessors called with:', { searchTerm, limit, sortBy });

            document.getElementById('professorsContainer').innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                    <p>Nalaganje profesorjev...</p>
                </div>
            `;

            try {
                let query = `
                    query {
                        profesorji(limit: ${limit}${searchTerm ? `, search: "${searchTerm}"` : ''}) {
                            id
                            ime
                            url
                            created_at
                            komentarji {
                                id
                                komentar
                                created_at
                            }
                        }
                    }
                `;

                console.log('Executing GraphQL query:', query);
                
                const result = await executeGraphQLQuery(query);
                
                console.log('GraphQL rezultat:', result);
                
                // Preveri, ali so podatki pravilno struktuirani
                if (!result.data || !result.data.profesorji) {
                    console.error('Neveljavna struktura odgovora:', result);
                    throw new Error('Neveljavna struktura odgovora iz GraphQL API-ja');
                }
                
                const profesorji = result.data.profesorji;
                console.log('Profesorji podatki:', profesorji);
                console.log('Število najdenih profesorjev:', profesorji.length);
                
                // Preveri, ali je profesorji array
                if (!Array.isArray(profesorji)) {
                    console.error('Profesorji niso array:', typeof profesorji, profesorji);
                    throw new Error('Profesorji niso v obliki seznama');
                }

                // Razvrsti profesorje
                if (sortBy === 'ime') {
                    profesorji.sort((a, b) => a.ime.localeCompare(b.ime));
                } else if (sortBy === 'komentarji') {
                    profesorji.sort((a, b) => (b.komentarji?.length || 0) - (a.komentarji?.length || 0));
                } else if (sortBy === 'datum') {
                    profesorji.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                }

                displayProfessors(profesorji);
                updateStatistics(profesorji);
            } catch (error) {
                console.error('Napaka pri nalaganju profesorjev:', error);
                document.getElementById('professorsContainer').innerHTML = `
                    <div class="error">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Napaka pri nalaganju profesorjev: ${error.message}
                    </div>
                `;
            }
        }

        // Prikaži profesorje
        function displayProfessors(profesorji) {
            console.log('displayProfessors called with:', profesorji);
            
            if (!Array.isArray(profesorji)) {
                console.error('displayProfessors: profesorji niso array');
                document.getElementById('professorsContainer').innerHTML = `
                    <div class="error">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Napaka: Neveljavni podatki o profesorjih
                    </div>
                `;
                return;
            }
            
            if (profesorji.length === 0) {
                console.log('No professors found');
                document.getElementById('professorsContainer').innerHTML = `
                    <div class="text-center">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Ni najdenih profesorjev</h4>
                        <p class="text-muted">Poskusite z drugačnim iskalnim izrazom</p>
                    </div>
                `;
                return;
            }

            console.log(`Displaying ${profesorji.length} professors`);
            
            let html = '<div class="row">';
            profesorji.forEach((prof, index) => {
                // Varno obravnavaj podatke
                const profId = prof.id || 'unknown';
                const profIme = prof.ime || 'Neznano ime';
                const profUrl = prof.url || '#';
                const profCreatedAt = prof.created_at || new Date().toISOString();
                const profKomentarji = Array.isArray(prof.komentarji) ? prof.komentarji : [];
                
                console.log(`Professor ${index + 1}:`, { profId, profIme, profKomentarji: profKomentarji.length });
                
                html += `
                    <div class="col-12">
                        <div class="card professor-card">
                            <div class="card-header professor-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-tie me-2"></i>
                                    ${profIme}
                                </h5>
                                <span class="badge bg-light text-dark">
                                    <i class="fas fa-comments me-1"></i>
                                    ${profKomentarji.length} komentarjev
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <p class="text-muted mb-3">
                                            <i class="fas fa-link me-1"></i>
                                            <a href="${profUrl}" target="_blank" class="text-decoration-none">
                                                ${profUrl}
                                            </a>
                                        </p>
                                        <p class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            Dodan: ${new Date(profCreatedAt).toLocaleDateString('sl-SI')}
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <button class="btn btn-outline-primary btn-sm" 
                                                onclick="toggleComments('${profId}')">
                                            <i class="fas fa-eye me-1"></i>
                                            Pokaži komentarje
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="comments-${profId}" class="mt-3" style="display: none;">
                                    <h6><i class="fas fa-comments me-2"></i>Komentarji:</h6>
                                    ${profKomentarji.length > 0 ? 
                                        profKomentarji.map(k => `
                                            <div class="comment-item">
                                                <p class="mb-1">${k.komentar || 'Neznan komentar'}</p>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    ${new Date(k.created_at || new Date()).toLocaleDateString('sl-SI')}
                                                </small>
                                            </div>
                                        `).join('') : 
                                        '<p class="text-muted">Ni komentarjev</p>'
                                    }
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            document.getElementById('professorsContainer').innerHTML = html;
            console.log('HTML generated and displayed');
        }

        // Posodobi statistiko
        function updateStatistics(profesorji) {
            if (!Array.isArray(profesorji)) {
                console.error('updateStatistics: profesorji niso array');
                return;
            }
            
            const totalProfessors = profesorji.length;
            const totalComments = profesorji.reduce((sum, prof) => {
                const commentCount = Array.isArray(prof.komentarji) ? prof.komentarji.length : 0;
                return sum + commentCount;
            }, 0);
            
            console.log(`Statistika: ${totalProfessors} profesorjev, ${totalComments} komentarjev`);
        }

        // Preklopi prikaz komentarjev
        function toggleComments(profId) {
            const commentsDiv = document.getElementById(`comments-${profId}`);
            const button = commentsDiv.previousElementSibling.querySelector('button');
            
            if (commentsDiv.style.display === 'none') {
                commentsDiv.style.display = 'block';
                button.innerHTML = '<i class="fas fa-eye-slash me-1"></i>Skrij komentarje';
            } else {
                commentsDiv.style.display = 'none';
                button.innerHTML = '<i class="fas fa-eye me-1"></i>Pokaži komentarje';
            }
        }

        // Naloži podatke ob nalaganju strani
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, calling loadProfessors');
            loadProfessors();
        });

        // Iskanje ob pritisku Enter
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                loadProfessors();
            }
        });

        // Avtomatsko osveževanje ob spremembi filtrov
        document.getElementById('limitSelect').addEventListener('change', loadProfessors);
        document.getElementById('sortSelect').addEventListener('change', loadProfessors);
    </script>
</body>
</html>
