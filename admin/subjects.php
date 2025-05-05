<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get all subjects with related information
$sql = "SELECT s.*, c.course_name, c.code as course_code 
        FROM subjects s 
        LEFT JOIN courses c ON s.course_id = c.id 
        ORDER BY s.created_at DESC";
$result = $conn->query($sql);

// Get all courses for the form
$courses = $conn->query("SELECT * FROM courses WHERE status = 'active'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management - SDC RMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2">Subject Management</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="fas fa-plus"></i> Add New Subject
                    </button>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Subjects Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Subject Code</th>
                                        <th>Subject Name</th>
                                        <th>Course</th>
                                        <th>Credits</th>
                                        <th>Theory Marks</th>
                                        <th>Practical Marks</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['subject_code']); ?></td>
                                        <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['credits']); ?></td>
                                        <td><?php echo htmlspecialchars($row['theory_marks']); ?></td>
                                        <td><?php echo htmlspecialchars($row['practical_marks']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-subject" 
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editSubjectModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-subject"
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteSubjectModal">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process/add_subject.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="course" class="form-label">Course</label>
                            <select class="form-select" id="course" name="course_id" required>
                                <option value="">Select Course</option>
                                <?php while($course = $courses->fetch_assoc()): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['code'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="subjectCode" class="form-label">Subject Code</label>
                            <input type="text" class="form-control" id="subjectCode" name="subject_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="subjectName" class="form-label">Subject Name</label>
                            <input type="text" class="form-control" id="subjectName" name="subject_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="credits" class="form-label">Credits</label>
                            <input type="number" class="form-control" id="credits" name="credits" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="theoryMarks" class="form-label">Theory Marks</label>
                            <input type="number" class="form-control" id="theoryMarks" name="theory_marks" value="60" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="practicalMarks" class="form-label">Practical Marks</label>
                            <input type="number" class="form-control" id="practicalMarks" name="practical_marks" value="40" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process/edit_subject.php" method="POST">
                    <input type="hidden" name="subject_id" id="editSubjectId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editCourse" class="form-label">Course</label>
                            <select class="form-select" id="editCourse" name="course_id" required>
                                <option value="">Select Course</option>
                                <?php 
                                $courses->data_seek(0);
                                while($course = $courses->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['course_name'] . ' (' . $course['code'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editSubjectCode" class="form-label">Subject Code</label>
                            <input type="text" class="form-control" id="editSubjectCode" name="subject_code" required>
                        </div>
                        <div class="mb-3">
                            <label for="editSubjectName" class="form-label">Subject Name</label>
                            <input type="text" class="form-control" id="editSubjectName" name="subject_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editCredits" class="form-label">Credits</label>
                            <input type="number" class="form-control" id="editCredits" name="credits" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="editTheoryMarks" class="form-label">Theory Marks</label>
                            <input type="number" class="form-control" id="editTheoryMarks" name="theory_marks" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="editPracticalMarks" class="form-label">Practical Marks</label>
                            <input type="number" class="form-control" id="editPracticalMarks" name="practical_marks" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editStatus" class="form-label">Status</label>
                            <select class="form-select" id="editStatus" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Subject Modal -->
    <div class="modal fade" id="deleteSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this subject? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="process/delete_subject.php" method="POST" class="d-inline">
                        <input type="hidden" name="subject_id" id="deleteSubjectId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Edit Subject
        $('.edit-subject').click(function() {
            var id = $(this).data('id');
            $.ajax({
                url: 'process/get_subject.php',
                type: 'POST',
                data: {id: id},
                success: function(response) {
                    var data = JSON.parse(response);
                    $('#editSubjectId').val(data.id);
                    $('#editCourse').val(data.course_id);
                    $('#editSubjectCode').val(data.subject_code);
                    $('#editSubjectName').val(data.subject_name);
                    $('#editCredits').val(data.credits);
                    $('#editTheoryMarks').val(data.theory_marks);
                    $('#editPracticalMarks').val(data.practical_marks);
                    $('#editDescription').val(data.description);
                    $('#editStatus').val(data.status);
                }
            });
        });

        // Delete Subject
        $('.delete-subject').click(function() {
            var id = $(this).data('id');
            $('#deleteSubjectId').val(id);
        });
    </script>
</body>
</html> 