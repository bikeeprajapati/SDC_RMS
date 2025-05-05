<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Get all classes with related information
$sql = "SELECT c.*, s.name as semester_name, y.name as year_name, sec.name as section_name 
        FROM classes c 
        LEFT JOIN semesters s ON c.semester_id = s.id 
        LEFT JOIN years y ON c.year_id = y.id 
        LEFT JOIN sections sec ON c.section_id = sec.id 
        ORDER BY c.created_at DESC";
$result = $conn->query($sql);

// Get all semesters, years, and sections for the form
$semesters = $conn->query("SELECT * FROM semesters WHERE status = 'active'");
$years = $conn->query("SELECT * FROM years WHERE status = 'active'");
$sections = $conn->query("SELECT * FROM sections WHERE status = 'active'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management - SDC RMS</title>
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
                    <h1 class="h2">Class Management</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                        <i class="fas fa-plus"></i> Add New Class
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

                <!-- Classes Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Class Name</th>
                                        <th>Semester</th>
                                        <th>Year</th>
                                        <th>Section</th>
                                        <th>Capacity</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['semester_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['year_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['section_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['capacity']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $row['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary edit-class" 
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editClassModal">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-class"
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteClassModal">
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

    <!-- Add Class Modal -->
    <div class="modal fade" id="addClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process/add_class.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="className" class="form-label">Class Name</label>
                            <input type="text" class="form-control" id="className" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="semester" class="form-label">Semester</label>
                            <select class="form-select" id="semester" name="semester_id" required>
                                <option value="">Select Semester</option>
                                <?php while($semester = $semesters->fetch_assoc()): ?>
                                    <option value="<?php echo $semester['id']; ?>">
                                        <?php echo htmlspecialchars($semester['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="year" class="form-label">Year</label>
                            <select class="form-select" id="year" name="year_id" required>
                                <option value="">Select Year</option>
                                <?php while($year = $years->fetch_assoc()): ?>
                                    <option value="<?php echo $year['id']; ?>">
                                        <?php echo htmlspecialchars($year['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="section" class="form-label">Section</label>
                            <select class="form-select" id="section" name="section_id" required>
                                <option value="">Select Section</option>
                                <?php while($section = $sections->fetch_assoc()): ?>
                                    <option value="<?php echo $section['id']; ?>">
                                        <?php echo htmlspecialchars($section['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" value="40" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Class</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Class Modal -->
    <div class="modal fade" id="editClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="process/edit_class.php" method="POST">
                    <input type="hidden" name="class_id" id="editClassId">
                    <div class="modal-body">
                        <!-- Similar fields as Add Class Modal -->
                        <div class="mb-3">
                            <label for="editClassName" class="form-label">Class Name</label>
                            <input type="text" class="form-control" id="editClassName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editSemester" class="form-label">Semester</label>
                            <select class="form-select" id="editSemester" name="semester_id" required>
                                <option value="">Select Semester</option>
                                <?php 
                                $semesters->data_seek(0);
                                while($semester = $semesters->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $semester['id']; ?>">
                                        <?php echo htmlspecialchars($semester['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editYear" class="form-label">Year</label>
                            <select class="form-select" id="editYear" name="year_id" required>
                                <option value="">Select Year</option>
                                <?php 
                                $years->data_seek(0);
                                while($year = $years->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $year['id']; ?>">
                                        <?php echo htmlspecialchars($year['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editSection" class="form-label">Section</label>
                            <select class="form-select" id="editSection" name="section_id" required>
                                <option value="">Select Section</option>
                                <?php 
                                $sections->data_seek(0);
                                while($section = $sections->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $section['id']; ?>">
                                        <?php echo htmlspecialchars($section['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="editCapacity" class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="editCapacity" name="capacity" min="1" required>
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
                        <button type="submit" class="btn btn-primary">Update Class</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Class Modal -->
    <div class="modal fade" id="deleteClassModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this class? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="process/delete_class.php" method="POST" class="d-inline">
                        <input type="hidden" name="class_id" id="deleteClassId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Edit Class
        $('.edit-class').click(function() {
            var id = $(this).data('id');
            $.ajax({
                url: 'process/get_class.php',
                type: 'POST',
                data: {id: id},
                success: function(response) {
                    var data = JSON.parse(response);
                    $('#editClassId').val(data.id);
                    $('#editClassName').val(data.name);
                    $('#editSemester').val(data.semester_id);
                    $('#editYear').val(data.year_id);
                    $('#editSection').val(data.section_id);
                    $('#editCapacity').val(data.capacity);
                    $('#editStatus').val(data.status);
                }
            });
        });

        // Delete Class
        $('.delete-class').click(function() {
            var id = $(this).data('id');
            $('#deleteClassId').val(id);
        });
    </script>
</body>
</html> 