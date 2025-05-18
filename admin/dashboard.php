<?php
require_once 'includes/header.php';

// Get dashboard statistics
function getDashboardStats() {
    global $conn;
    
    $stats = [
        'total_students' => 0,
        'total_subjects' => 0,
        'total_results' => 0,
        'active_notices' => 0,
        'grade_distribution' => [],
        'subject_performance' => [],
        'top_performers' => []
    ];
    
    try {
        // Get total active students
        $result = $conn->query("SELECT COUNT(*) as count FROM students");
        $stats['total_students'] = $result->fetch_assoc()['count'];
        
        // Get total active subjects
        $result = $conn->query("SELECT COUNT(*) as count FROM subjects");
        $stats['total_subjects'] = $result->fetch_assoc()['count'];
        
        // Get total results
        $result = $conn->query("SELECT COUNT(*) as count FROM results");
        $stats['total_results'] = $result->fetch_assoc()['count'];
        
        // Get active notices
        $result = $conn->query("SELECT COUNT(*) as count FROM notices WHERE is_active = 1");
        $stats['active_notices'] = $result->fetch_assoc()['count'];

        // Get grade distribution
        $result = $conn->query("
            SELECT grade, COUNT(*) as count 
            FROM results 
            GROUP BY grade 
            ORDER BY grade
        ");
        $stats['grade_distribution'] = $result->fetch_all(MYSQLI_ASSOC);

        // Get subject performance
        $result = $conn->query("
            SELECT 
                sub.subject_code,
                sub.subject_name,
                COUNT(*) as total_results,
                ROUND(AVG(r.marks/sub.max_marks * 100), 2) as avg_percentage,
                ROUND(AVG(r.grade_point), 2) as avg_grade_point
            FROM results r
            JOIN subjects sub ON r.subject_id = sub.id
            GROUP BY r.subject_id
            ORDER BY avg_grade_point DESC
        ");
        $stats['subject_performance'] = $result->fetch_all(MYSQLI_ASSOC);

        // Get top performers
        $result = $conn->query("
            SELECT 
                s.id,
                s.roll_number,
                s.full_name as student_name,
                COUNT(DISTINCT r.id) as total_exams,
                ROUND(AVG(r.grade_point), 2) as avg_grade_point,
                GROUP_CONCAT(DISTINCT CONCAT(sub.subject_code, ': ', r.grade) ORDER BY sub.subject_code) as grades
            FROM students s
            JOIN results r ON s.id = r.student_id
            JOIN subjects sub ON r.subject_id = sub.id
            GROUP BY s.id, s.roll_number, s.full_name
            HAVING total_exams >= 3
            ORDER BY avg_grade_point DESC, total_exams DESC
            LIMIT 5
        ");
        $stats['top_performers'] = $result->fetch_all(MYSQLI_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error fetching dashboard stats: " . $e->getMessage());
    }
    
    return $stats;
}

// Get recent results
function getRecentResults($limit = 5) {
    global $conn;
    
    $results = [];
    
    try {
        $stmt = $conn->query("
            SELECT r.*, 
                s.roll_number,
                s.full_name as student_name,
                sub.subject_code,
                sub.subject_name,
                et.type_name as exam_type,
                sub.max_marks
            FROM results r
            JOIN students s ON r.student_id = s.id
            JOIN subjects sub ON r.subject_id = sub.id
            JOIN exam_types et ON r.exam_type_id = et.id
            ORDER BY r.created_at DESC
            LIMIT $limit
        ");
        
        while ($row = $stmt->fetch_assoc()) {
            $results[] = $row;
        }
    } catch (Exception $e) {
        error_log("Error fetching recent results: " . $e->getMessage());
    }
    
    return $results;
}

// Get recent notices
function getRecentNotices($limit = 5) {
    global $conn;
    
    $notices = [];
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM notices 
            WHERE status = 'active' 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $notices[] = $row;
        }
    } catch (Exception $e) {
        error_log("Error fetching recent notices: " . $e->getMessage());
    }
    
    return $notices;
}

$stats = getDashboardStats();
$recentResults = getRecentResults();
$recentNotices = getRecentNotices();
?>

<h1 class="h2 mb-4">Dashboard</h1>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card h-100 border-primary">
            <div class="card-body">
                <h5 class="card-title">Total Students</h5>
                <h2 class="card-text"><?php echo number_format($stats['total_students']); ?></h2>
                <p class="card-text text-muted">Active students</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card h-100 border-success">
            <div class="card-body">
                <h5 class="card-title">Total Subjects</h5>
                <h2 class="card-text"><?php echo number_format($stats['total_subjects']); ?></h2>
                <p class="card-text text-muted">Active subjects</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card h-100 border-info">
            <div class="card-body">
                <h5 class="card-title">Total Results</h5>
                <h2 class="card-text"><?php echo number_format($stats['total_results']); ?></h2>
                <p class="card-text text-muted">Results recorded</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card h-100 border-warning">
            <div class="card-body">
                <h5 class="card-title">Active Notices</h5>
                <h2 class="card-text"><?php echo number_format($stats['active_notices']); ?></h2>
                <p class="card-text text-muted">Currently published</p>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Subject Performance Overview</h5>
            </div>
            <div class="card-body">
                <div class="chart-area" style="height: 300px;">
                    <canvas id="subjectPerformanceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Grade Distribution</h5>
            </div>
            <div class="card-body">
                <div class="chart-pie" style="height: 300px;">
                    <canvas id="gradeDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Results</h5>
                <a href="results.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Subject</th>
                                <th>Exam Type</th>
                                <th>Marks</th>
                                <th>Grade</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentResults as $result): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($result['roll_number'] . ' - ' . $result['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($result['subject_code'] . ' - ' . $result['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($result['exam_type']); ?></td>
                                <td><?php echo $result['marks'] . ' / ' . $result['max_marks']; ?></td>
                                <td><?php echo $result['grade'] . ' (' . $result['grade_point'] . ')'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($result['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentResults)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No recent results</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Top Performers</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Avg. GPA</th>
                                <th>Exams</th>
                                <th>Grades</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['top_performers'] as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['roll_number'] . ' - ' . $student['student_name']); ?></td>
                                <td><strong><?php echo number_format($student['avg_grade_point'], 2); ?></strong></td>
                                <td><?php echo $student['total_exams']; ?></td>
                                <td><small><?php echo htmlspecialchars($student['grades']); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stats['top_performers'])): ?>
                            <tr>
                                <td colspan="4" class="text-center">No data available</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    // Subject Performance Chart
    const subjectData = <?php echo json_encode($stats['subject_performance']); ?>;
    new Chart(document.getElementById('subjectPerformanceChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: subjectData.map(item => item.subject_code),
            datasets: [{
                label: 'Average Percentage',
                data: subjectData.map(item => item.avg_percentage),
                backgroundColor: 'rgba(78, 115, 223, 0.5)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 1
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });

    // Grade Distribution Chart
    const gradeData = <?php echo json_encode($stats['grade_distribution']); ?>;
    new Chart(document.getElementById('gradeDistributionChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: gradeData.map(item => item.grade),
            datasets: [{
                data: gradeData.map(item => item.count),
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc',
                    '#f6c23e', '#e74a3b', '#858796'
                ]
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 