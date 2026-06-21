<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Type</th>
                <th>Count</th>
                <th>Average Rating</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($report_data as $row): ?>
                <tr>
                    <td><?php echo ucfirst($row['type']); ?></td>
                    <td><?php echo (int)$row['count']; ?></td>
                    <td><?php echo number_format((float)$row['avg_rating'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
