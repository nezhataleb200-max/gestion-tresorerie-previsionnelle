/**
 * dashboard-charts.js
 *
 * Ce fichier crée les 3 graphiques du dashboard avec Chart.js.
 *
 * Chart.js est une bibliothèque JavaScript qui dessine des graphiques
 * sur un élément <canvas> HTML. On lui donne des données et une
 * configuration, elle fait tout le travail visuel.
 *
 * Les données viennent du PHP via la variable GRAPHIQUE_DATA
 * définie dans la vue Blade avec @json().
 */

// Attendre que le DOM soit entièrement chargé avant de dessiner
document.addEventListener('DOMContentLoaded', function () {

    // ── GRAPHIQUE 1 : BARRES GROUPÉES ─────────────────────────
    /**
     * Ce graphique montre côte à côte, pour chaque mois,
     * les entrées (vert) et les sorties (rouge).
     * Si les barres rouges dépassent les barres vertes → ce mois est déficitaire.
     */
    const ctxBarres = document.getElementById('chartBarres');
    if (ctxBarres) {
        new Chart(ctxBarres, {
            // type: 'bar' = graphique en barres verticales
            type: 'bar',

            data: {
                // labels = les noms des mois sur l'axe horizontal (X)
                labels: GRAPHIQUE_DATA.labels,

                // datasets = les séries de données
                // Chaque dataset = une couleur de barre
                datasets: [
                    {
                        label: 'Entrées (MAD)',
                        data: GRAPHIQUE_DATA.entrees,
                        backgroundColor: 'rgba(29, 158, 117, 0.7)', // vert semi-transparent
                        borderColor: 'rgba(29, 158, 117, 1)',        // vert opaque pour le contour
                        borderWidth: 1,
                        borderRadius: 4, // coins arrondis des barres
                    },
                    {
                        label: 'Sorties (MAD)',
                        data: GRAPHIQUE_DATA.sorties,
                        backgroundColor: 'rgba(226, 75, 74, 0.7)',  // rouge semi-transparent
                        borderColor: 'rgba(226, 75, 74, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                    }
                ]
            },

            options: {
                responsive: true,     // s'adapte à la taille du conteneur
                maintainAspectRatio: true,

                plugins: {
                    legend: {
                        position: 'top', // légende en haut du graphique
                    },
                    tooltip: {
                        callbacks: {
                            // Personnalise l'info-bulle qui apparaît au survol
                            label: function(context) {
                                return context.dataset.label + ' : ' +
                                    new Intl.NumberFormat('fr-MA').format(context.raw) + ' MAD';
                            }
                        }
                    }
                },

                scales: {
                    // Axe Y (vertical) = les montants
                    y: {
                        beginAtZero: true, // commence à 0
                        ticks: {
                            // Formate les nombres sur l'axe Y
                            callback: function(value) {
                                if (value >= 1000) {
                                    return (value / 1000).toFixed(0) + 'k MAD';
                                }
                                return value + ' MAD';
                            }
                        }
                    },
                    // Axe X (horizontal) = les mois
                    x: {
                        grid: {
                            display: false // cache les lignes verticales de la grille
                        }
                    }
                }
            }
        });
    }


    // ── GRAPHIQUE 2 : COURBE SOLDE CUMULÉ ─────────────────────
    /**
     * Ce graphique montre l'évolution du solde cumulé mois par mois.
     * Quand la courbe passe sous zéro → déficit → zone rouge.
     * C'est la visualisation la plus importante pour la trésorerie.
     */
    const ctxCourbe = document.getElementById('chartCourbe');
    if (ctxCourbe) {
        new Chart(ctxCourbe, {
            // type: 'line' = graphique en courbe
            type: 'line',

            data: {
                labels: GRAPHIQUE_DATA.labels,
                datasets: [
                    {
                        label: 'Solde cumulé (MAD)',
                        data: GRAPHIQUE_DATA.cumules,

                        // Couleur de la ligne : bleue
                        borderColor: 'rgba(56, 138, 221, 1)',
                        borderWidth: 2,

                        // Remplissage sous la courbe
                        // 'origin' = remplit depuis la ligne zéro
                        fill: 'origin',

                        // La couleur de remplissage change selon si
                        // la valeur est positive ou négative
                        backgroundColor: function(context) {
                            const chart = context.chart;
                            const { ctx, chartArea } = chart;
                            if (!chartArea) return null;

                            // Dégradé : vert au-dessus de zéro, rouge en dessous
                            const gradient = ctx.createLinearGradient(
                                0, chartArea.top, 0, chartArea.bottom
                            );
                            gradient.addColorStop(0, 'rgba(29, 158, 117, 0.15)');   // vert haut
                            gradient.addColorStop(0.5, 'rgba(56, 138, 221, 0.1)'); // bleu milieu
                            gradient.addColorStop(1, 'rgba(226, 75, 74, 0.15)');    // rouge bas
                            return gradient;
                        },

                        // tension: 0.4 = la courbe est légèrement arrondie
                        // (0 = droite, 1 = très arrondie)
                        tension: 0.4,

                        // Affiche un point à chaque mois
                        pointRadius: 5,
                        pointHoverRadius: 7,

                        // Couleur des points : vert si positif, rouge si négatif
                        pointBackgroundColor: function(context) {
                            const value = context.dataset.data[context.dataIndex];
                            return value >= 0
                                ? 'rgba(29, 158, 117, 1)'   // vert
                                : 'rgba(226, 75, 74, 1)';   // rouge
                        },
                    }
                ]
            },

            options: {
                responsive: true,
                maintainAspectRatio: true,

                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const val = context.raw;
                                const signe = val >= 0 ? '+' : '';
                                return 'Solde cumulé : ' + signe +
                                    new Intl.NumberFormat('fr-MA').format(val) + ' MAD';
                            }
                        }
                    }
                },

                scales: {
                    y: {
                        // Ligne horizontale à zéro bien visible
                        grid: {
                            color: function(context) {
                                // La ligne zéro est plus foncée que les autres
                                return context.tick.value === 0
                                    ? 'rgba(0,0,0,0.3)'
                                    : 'rgba(0,0,0,0.05)';
                            }
                        },
                        ticks: {
                            callback: function(value) {
                                const signe = value >= 0 ? '+' : '';
                                if (Math.abs(value) >= 1000) {
                                    return signe + (value / 1000).toFixed(0) + 'k';
                                }
                                return signe + value;
                            }
                        }
                    }
                }
            }
        });
    }


    // ── GRAPHIQUE 3 : CAMEMBERT CHARGES ───────────────────────
    /**
     * Ce graphique montre la répartition des charges du mois
     * par catégorie (loyer, salaires, impôts, etc.)
     * C'est un "doughnut" (beignet) = camembert avec trou au centre.
     */
    const ctxCamembert = document.getElementById('chartCamembert');
    if (ctxCamembert) {

        // Couleurs pour chaque catégorie de charges
        const couleurs = [
            'rgba(83, 74, 183, 0.8)',  // loyer → violet
            'rgba(29, 158, 117, 0.8)', // salaires → vert
            'rgba(186, 117, 23, 0.8)', // impôts → ambre
            'rgba(226, 75, 74, 0.8)',  // fournisseurs → rouge
            'rgba(56, 138, 221, 0.8)', // services → bleu
            'rgba(136, 135, 128, 0.8)',// autre → gris
        ];

        new Chart(ctxCamembert, {
            // type: 'doughnut' = camembert avec trou au centre
            type: 'doughnut',

            data: {
                // Noms des catégories
                labels: CAMEMBERT_DATA.labels.length > 0
                    ? CAMEMBERT_DATA.labels
                    : ['Aucune charge ce mois'],

                datasets: [{
                    // Montants par catégorie
                    data: CAMEMBERT_DATA.valeurs.length > 0
                        ? CAMEMBERT_DATA.valeurs
                        : [1],

                    backgroundColor: couleurs.slice(0, CAMEMBERT_DATA.labels.length || 1),
                    borderWidth: 2,
                    borderColor: '#fff',
                    // Effet au survol : la tranche s'agrandit légèrement
                    hoverOffset: 8,
                }]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                // cutout: taille du trou au centre (en pourcentage)
                cutout: '60%',

                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (CAMEMBERT_DATA.valeurs.length === 0) return '';
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const pct   = ((context.raw / total) * 100).toFixed(1);
                                return context.label + ' : ' +
                                    new Intl.NumberFormat('fr-MA').format(context.raw) +
                                    ' MAD (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

}); // fin DOMContentLoaded