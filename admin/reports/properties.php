<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Property Type</th>
                <th>Count</th>
                <th>Average Rent (ETB)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($report_data as $row): ?>
                <tr>
                    <td><?php echo ucfirst($row['property_type']); ?></td>
                    <td><?php echo (int)$row['count']; ?></td>
                    <td><?php echo number_format((float)$row['avg_rent'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
