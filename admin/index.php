<?php
require_once 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Students</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-students">Loading...</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Subjects</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-subjects">Loading...</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-book fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Results</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-results">Loading...</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-card-checklist fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Average Grade Point</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="avg-grade-point">Loading...</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-graph-up fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Subject Performance Overview</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="subjectPerformanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Grade Distribution</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie">
                        <canvas id="gradeDistributionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Row -->
    <div class="row">
        <!-- Recent Results -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Results</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
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
                            <tbody id="recent-results">
                                <tr>
                                    <td colspan="6" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performers -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Performers</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Avg. GPA</th>
                                    <th>Exams</th>
                                </tr>
                            </thead>
                            <tbody id="top-performers">
                                <tr>
                                    <td colspan="3" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Chart configurations
let subjectPerformanceChart = null;
let gradeDistributionChart = null;

// Function to initialize charts
function initializeCharts() {
    const subjectCtx = document.getElementById('subjectPerformanceChart').getContext('2d');
    const gradeCtx = document.getElementById('gradeDistributionChart').getContext('2d');

    // Subject Performance Chart
    subjectPerformanceChart = new Chart(subjectCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Average Percentage',
                data: [],
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
    gradeDistributionChart = new Chart(gradeCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
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
}

// Function to update dashboard data
function updateDashboard() {
    fetch('get_dashboard_data.php')
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                const data = response.data;

                // Update statistics
                document.getElementById('total-students').textContent = data.total_students;
                document.getElementById('total-subjects').textContent = data.total_subjects;
                document.getElementById('total-results').textContent = data.total_results;

                // Update grade distribution chart
                const grades = data.grade_distribution.map(item => item.grade);
                const gradeCounts = data.grade_distribution.map(item => item.count);
                gradeDistributionChart.data.labels = grades;
                gradeDistributionChart.data.datasets[0].data = gradeCounts;
                gradeDistributionChart.update();

                // Update subject performance chart
                const subjects = data.subject_performance.map(item => item.subject_code);
                const percentages = data.subject_performance.map(item => item.avg_percentage);
                subjectPerformanceChart.data.labels = subjects;
                subjectPerformanceChart.data.datasets[0].data = percentages;
                subjectPerformanceChart.update();

                // Update recent results table
                const recentResultsHtml = data.recent_results.map(result => `
                    <tr>
                        <td>${result.roll_number} - ${result.student_name}</td>
                        <td>${result.subject_code} - ${result.subject_name}</td>
                        <td>${result.exam_type}</td>
                        <td>${result.marks}</td>
                        <td>${result.grade} (${result.grade_point})</td>
                        <td>${new Date(result.created_at).toLocaleDateString()}</td>
                    </tr>
                `).join('');
                document.getElementById('recent-results').innerHTML = recentResultsHtml;

                // Update top performers table
                const topPerformersHtml = data.top_performers.map(student => `
                    <tr>
                        <td>${student.roll_number} - ${student.student_name}</td>
                        <td>${student.avg_grade_point}</td>
                        <td>${student.total_exams}</td>
                    </tr>
                `).join('');
                document.getElementById('top-performers').innerHTML = topPerformersHtml;
            }
        })
        .catch(error => {
            console.error('Error fetching dashboard data:', error);
        });
}

// Initialize charts when the page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    updateDashboard();
    
    // Update dashboard every 30 seconds
    setInterval(updateDashboard, 30000);
});
</script>

<?php require_once 'includes/footer.php'; ?> 