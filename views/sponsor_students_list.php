<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <!-- Header -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-graduation-cap"></i> My Sponsored Students
                        </h3>
                        <div class="pull-right">
                            <a href="<?= admin_url('student_sponsor_portal/sponsor_profile') ?>" class="btn btn-sm btn-default">
                                <i class="fa fa-user"></i> Back to Profile
                            </a>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><i class="fa fa-filter"></i> Filter Students</h3>
                    </div>
                    <div class="panel-body">
                        <form method="GET" action="<?= admin_url('student_sponsor_portal/my_sponsored_students') ?>" id="filterForm">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="type">Student Type:</label>
                                        <select name="type" id="type" class="form-control" onchange="document.getElementById('filterForm').submit();">
                                            <option value="all" <?= $current_filter === 'all' ? 'selected' : '' ?>>All Students</option>
                                            <option value="school" <?= $current_filter === 'school' ? 'selected' : '' ?>>School Students Only</option>
                                            <option value="university" <?= $current_filter === 'university' ? 'selected' : '' ?>>University Students Only</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="search">Search:</label>
                                        <input type="text" 
                                               name="search" 
                                               id="search" 
                                               class="form-control" 
                                               placeholder="Search by name, ID, or city..." 
                                               value="<?= htmlspecialchars($current_search) ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>&nbsp;</label><br>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-search"></i> Search
                                        </button>
                                        <a href="<?= admin_url('student_sponsor_portal/my_sponsored_students') ?>" class="btn btn-default">
                                            <i class="fa fa-refresh"></i> Clear
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row">
            <div class="col-md-4">
                <div class="panel panel-info">
                    <div class="panel-body text-center">
                        <h3><?= count($students) ?></h3>
                        <p>
                            <?php if ($current_filter === 'school'): ?>
                                School Students
                            <?php elseif ($current_filter === 'university'): ?>
                                University Students
                            <?php else: ?>
                                Total Students
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel panel-success">
                    <div class="panel-body text-center">
                        <h3>Amt <?= number_format(array_sum(array_column(array_column($students, 'transaction_summary'), 'total_amount')), 0) ?></h3>
                        <p>Total Committed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel panel-warning">
                    <div class="panel-body text-center">
                        <h3>Amt <?= number_format(array_sum(array_column(array_column($students, 'transaction_summary'), 'amount_paid')), 0) ?></h3>
                        <p>Total Paid</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students List -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-list"></i> Students List
                            <?php if (!empty($current_search)): ?>
                                - Search results for "<?= htmlspecialchars($current_search) ?>"
                            <?php endif; ?>
                        </h3>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($students)): ?>
                            <div class="alert alert-warning text-center">
                                <i class="fa fa-exclamation-triangle fa-2x"></i><br>
                                <strong>No Students Found</strong><br>
                                <?php if (!empty($current_search)): ?>
                                    No students match your search criteria.
                                <?php elseif ($current_filter !== 'all'): ?>
                                    You don't have any <?= $current_filter ?> students.
                                <?php else: ?>
                                    You don't have any sponsored students yet.
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="studentsTable">
                                    <thead>
                                        <tr>
                                            <th>Student Name</th>
                                            <th>Type</th>
                                            <th>Student ID</th>
                                            <th>Grade/Year</th>
                                            <th>Institution</th>
                                            <th>Location</th>
                                            <th>Sponsorship Amount</th>
                                            <th>Amount Paid</th>
                                            <th>Balance</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($student['name']) ?></strong>
                                                <?php if (!empty($student['email'])): ?>
                                                    <br><small class="text-muted">
                                                        <i class="fa fa-envelope"></i> <?= htmlspecialchars($student['email']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="label label-<?= $student['student_type'] === 'school' ? 'primary' : 'info' ?>">
                                                    <?= ucfirst($student['student_type']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <code><?= htmlspecialchars($student['student_type'] === 'school' ? $student['school_internal_id'] : $student['university_internal_id']) ?></code>
                                            </td>
                                            <td>
                                                <?php if ($student['student_type'] === 'school'): ?>
                                                    Grade <?= htmlspecialchars($student['school_grade']) ?>
                                                <?php else: ?>
                                                    Year <?= htmlspecialchars($student['university_year_of_study']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($student['student_type'] === 'school'): ?>
                                                    <?= htmlspecialchars($student['school_name'] ?? 'Not specified') ?>
                                                <?php else: ?>
                                                    <?= htmlspecialchars($student['university_name'] ?? 'Not specified') ?>
                                                    <?php if (!empty($student['program_name'])): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($student['program_name']) ?></small>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($student['city'] ?? 'Not provided') ?></td>
                                            <td>
                                                <strong class="text-primary">
                                                    Amt <?= number_format($student['transaction_summary']['total_amount'] ?? 0, 0) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <strong class="text-success">
                                                    Amt <?= number_format($student['transaction_summary']['amount_paid'] ?? 0, 0) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <strong class="text-danger">
                                                    Amt <?= number_format($student['transaction_summary']['balance_amount'] ?? 0, 0) ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <!-- FIXED: Use the alternative URL format with query parameters -->
                                                <a href="<?= admin_url('student_sponsor_portal/student_detail?type=' . $student['student_type'] . '&id=' . $student['id']) ?>" 
                                                   class="btn btn-sm btn-info" title="View Student Details">
                                                    <i class="fa fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="info">
                                            <th colspan="6">Totals:</th>
                                            <th class="text-primary">
                                                Amt <?= number_format(array_sum(array_column(array_column($students, 'transaction_summary'), 'total_amount')), 0) ?>
                                            </th>
                                            <th class="text-success">
                                                Amt <?= number_format(array_sum(array_column(array_column($students, 'transaction_summary'), 'amount_paid')), 0) ?>
                                            </th>
                                            <th class="text-danger">
                                                Amt <?= number_format(array_sum(array_column(array_column($students, 'transaction_summary'), 'balance_amount')), 0) ?>
                                            </th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    if ($.fn.DataTable && $('#studentsTable').length) {
        $('#studentsTable').DataTable({
            responsive: true,
            pageLength: 15,
            order: [[0, 'asc']],
            columnDefs: [
                { targets: [9], orderable: false } // Actions column
            ],
            footerCallback: function (row, data, start, end, display) {
                // Footer totals are already calculated in PHP
            }
        });
    }

    // Auto-submit search after typing (with delay)
    let searchTimeout;
    $('#search').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            $('#filterForm').submit();
        }, 1000); // Wait 1 second after user stops typing
    });
});
</script>

<style>
.label {
    font-size: 12px;
    padding: 4px 8px;
}

.table th, .table td {
    vertical-align: middle;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}
</style>

<?php init_tail(); ?>