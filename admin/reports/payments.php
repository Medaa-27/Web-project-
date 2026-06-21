<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Payments</th>
                <th>Total Amount (ETB)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($report_data as $row): ?>
                <tr>
                    <td><?php echo formatDate($row['date'], 'M d, Y'); ?></td>
                    <td><?php echo (int)$row['payments']; ?></td>
                    <td><?php echo number_format((float)$row['total_amount'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
