<?php
require_once 'includes/db.php';
require_once 'includes/analytics.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$analyticsData = $analytics->getAnalyticsData('7d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Analytics Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .dashboard-header {
            background-color: #f8f9fa;
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid #dee2e6;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
            height: 100%;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            height: 100%;
        }
        .chart-header {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .difficult-questions {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .progress-thin {
            height: 8px;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <div class="container">
            <h1 class="display-4">Quiz Analytics Dashboard</h1>
            <p class="lead text-muted">Comprehensive overview of quiz performance and participation</p>
        </div>
    </div>

    <div class="container">
        <!-- Key Metrics Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-value"><?= number_format($analyticsData['page_views']) ?></div>
                    <div class="stat-label">Page Views</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-value"><?= number_format($analyticsData['quiz_starts']) ?></div>
                    <div class="stat-label">Quiz Starts</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-value"><?= number_format($analyticsData['quiz_completions']) ?></div>
                    <div class="stat-label">Quiz Completions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card text-center">
                    <div class="stat-value"><?= round($analyticsData['completion_rate'], 1) ?>%</div>
                    <div class="stat-label">Completion Rate</div>
                </div>
            </div>
        </div>

        <!-- Main Content Row -->
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Participation Chart -->
                <div class="chart-container">
                    <div class="chart-header">Quiz Participation</div>
                    <canvas id="quizParticipationChart"></canvas>
                </div>

                <!-- Average Score Chart -->
                <div class="chart-container">
                    <div class="chart-header">Average Score Breakdown</div>
                    <canvas id="averageScoresChart"></canvas>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Current Average</span>
                            <span><?= round($analyticsData['avg_score'], 1) ?>%</span>
                        </div>
                        <div class="progress progress-thin">
                            <div class="progress-bar bg-success" 
                                 role="progressbar" 
                                 style="width: <?= round($analyticsData['avg_score'], 1) ?>%" 
                                 aria-valuenow="<?= round($analyticsData['avg_score'], 1) ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Popular Quizzes -->
                <div class="chart-container">
                    <div class="chart-header">Top Performing Quizzes</div>
                    <?php if (!empty($analyticsData['popular_quizzes'])): ?>
                        <div class="list-group">
                            <?php foreach ($analyticsData['popular_quizzes'] as $quiz): 
                                // Skip if quiz title is empty or invalid
                                if (empty($quiz['quiz_title'])) continue;
                                
                                // Skip if attempts is 2 and avg score is 50% (the entry we want to remove)
                                if ($quiz['attempts'] == 2 && $quiz['avg_score_percentage'] == 50) continue;
                                
                                // Ensure valid score percentage
                                $scorePercentage = min(100, max(0, round($quiz['avg_score_percentage'], 1)));
                                $quizTitle = htmlspecialchars($quiz['quiz_title'] ?: 'Unknown Quiz');
                            ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= $quizTitle ?></h6>
                                        <small><?= (int)$quiz['attempts'] ?> attempt<?= $quiz['attempts'] != 1 ? 's' : '' ?></small>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small>Avg Score: <?= $scorePercentage ?>%</small>
                                        <div class="progress progress-thin" style="width: 60%">
                                            <div class="progress-bar bg-info" 
                                                role="progressbar" 
                                                style="width: <?= $scorePercentage ?>%" 
                                                aria-valuenow="<?= $scorePercentage ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No quiz data available yet.</div>
                    <?php endif; ?>
                </div>

                <!-- Difficult Questions -->
                <div class="chart-container">
                    <div class="chart-header">Most Difficult Questions</div>
                    <?php if (!empty($analyticsData['difficult_questions'])): ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Question ID</th>
                                    <th>Correct %</th>
                                    <th style="width: 40%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($analyticsData['difficult_questions'] as $question): ?>
                                <tr>
                                    <td><?= htmlspecialchars(substr($question['question_id'], 0, 8)) ?>...</td>
                                    <td><?= round($question['correct_percentage'], 1) ?>%</td>
                                    <td>
                                        <div class="progress progress-thin">
                                            <div class="progress-bar bg-danger" 
                                                 role="progressbar" 
                                                 style="width: <?= round($question['correct_percentage'], 1) ?>%" 
                                                 aria-valuenow="<?= round($question['correct_percentage'], 1) ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">No question data available yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Quiz Participation Chart
        const participationCtx = document.getElementById('quizParticipationChart').getContext('2d');
        new Chart(participationCtx, {
            type: 'bar',
            data: {
                labels: ['Page Views', 'Quiz Starts', 'Quiz Completions'],
                datasets: [{
                    label: 'Count',
                    data: [
                        <?= $analyticsData['page_views'] ?>,
                        <?= $analyticsData['quiz_starts'] ?>,
                        <?= $analyticsData['quiz_completions'] ?>
                    ],
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)'
                    ],
                    borderColor: [
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.raw;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
        
        // Average Scores Chart
        const scoresCtx = document.getElementById('averageScoresChart').getContext('2d');
        new Chart(scoresCtx, {
            type: 'doughnut',
            data: {
                labels: ['Average Score', 'Remaining'],
                datasets: [{
                    data: [
                        <?= round($analyticsData['avg_score'], 2) ?>,
                        <?= 100 - round($analyticsData['avg_score'], 2) ?>
                    ],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(201, 203, 207, 0.7)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(201, 203, 207, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.raw.toFixed(2) + '%';
                            }
                        }
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>