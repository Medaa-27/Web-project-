<?php
// System Overview Report Display
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body text-center">
                <h4><?php echo $report_data['users']['total_users'] ?? 0; ?></h4>
                <p class="mb-0">Total Users</p>
                <small><?php echo $report_data['users']['active_users'] ?? 0; ?> Active</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body text-center">
                <h4><?php echo $report_data['properties']['total_properties'] ?? 0; ?></h4>
                <p class="mb-0">Properties</p>
                <small><?php echo $report_data['properties']['available_properties'] ?? 0; ?> Available</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body text-center">
                <h4><?php echo $report_data['rentals']['total_rentals'] ?? 0; ?></h4>
                <p class="mb-0">Rental Agreements</p>
                <small><?php echo $report_data['rentals']['active_rentals'] ?? 0; ?> Active</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body text-center">
                <h4><?php echo number_format($report_data['payments']['total_revenue'] ?? 0); ?></h4>
                <p class="mb-0">Total Revenue</p>
                <small><?php echo $report_data['payments']['total_payments'] ?? 0; ?> Payments</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">User Statistics</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Total Users:</td>
                        <td><strong><?php echo $report_data['users']['total_users'] ?? 0; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Active Users:</td>
                        <td><strong><?php echo $report_data['users']['active_users'] ?? 0; ?></strong></td>
                    </tr>
                    <tr>
                        <td>New Users (Period):</td>
                        <td><strong><?php echo $report_data['users']['new_users'] ?? 0; ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Financial Summary</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Total Payments:</td>
                        <td><strong><?php echo $report_data['payments']['total_payments'] ?? 0; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Total Revenue:</td>
                        <td><strong><?php echo number_format($report_data['payments']['total_revenue'] ?? 0); ?></strong></td>
                    </tr>
                    <tr>
                        <td>Recent Payments:</td>
                        <td><strong><?php echo $report_data['payments']['recent_payments'] ?? 0; ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <canvas id="overviewChart" width="400" height="200"></canvas>
</div>

<script>
// Overview Chart
const ctx = document.getElementById('overviewChart').getContext('2d');
const overviewChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Users', 'Properties', 'Rentals', 'Payments'],
        datasets: [{
            label: 'Total Count',
            data: [
                <?php echo $report_data['users']['total_users'] ?? 0; ?>,
                <?php echo $report_data['properties']['total_properties'] ?? 0; ?>,
                <?php echo $report_data['rentals']['total_rentals'] ?? 0; ?>,
                <?php echo $report_data['payments']['total_payments'] ?? 0; ?>
            ],
            backgroundColor: [
                'rgba(54, 162, 235, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 206, 86, 0.8)',
                'rgba(255, 99, 132, 0.8)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>
