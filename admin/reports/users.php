<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Role</th>
                <th>Count</th>
                <th>New Users</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($report_data as $row): ?>
                <tr>
                    <td><?php echo ucfirst($row['role']); ?></td>
                    <td><?php echo (int)$row['count']; ?></td>
                    <td><?php echo (int)$row['new_users']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
