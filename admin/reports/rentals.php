<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>Rentals</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($report_data as $row): ?>
                <tr>
                    <td><?php echo formatDate($row['date'], 'M d, Y'); ?></td>
                    <td><?php echo (int)$row['rentals']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
