<?php
// This file is included in property-review.php modal
// $property variable is available from parent scope
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="text-muted">Basic Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Title:</strong></td>
                <td><?php echo htmlspecialchars($property['title']); ?></td>
            </tr>
            <tr>
                <td><strong>Type:</strong></td>
                <td><?php echo ucfirst($property['property_type']); ?></td>
            </tr>
            <tr>
                <td><strong>Bedrooms:</strong></td>
                <td><?php echo $property['bedrooms']; ?></td>
            </tr>
            <tr>
                <td><strong>Bathrooms:</strong></td>
                <td><?php echo $property['bathrooms']; ?></td>
            </tr>
            <tr>
                <td><strong>Area:</strong></td>
                <td><?php echo $property['area_sqm']; ?> sqm</td>
            </tr>
            <tr>
                <td><strong>Furnished:</strong></td>
                <td><?php echo $property['is_furnished'] ? 'Yes' : 'No'; ?></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h6 class="text-muted">Financial Information</h6>
        <table class="table table-sm">
            <tr>
                <td><strong>Monthly Rent:</strong></td>
                <td>ETB <?php echo number_format($property['monthly_rent'], 2); ?></td>
            </tr>
            <tr>
                <td><strong>Security Deposit:</strong></td>
                <td>ETB <?php echo number_format($property['security_deposit'] ?? 0, 2); ?></td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td>
                    <?php
                    $status_colors = [
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'needs_revision' => 'info'
                    ];
                    $color = $status_colors[$property['review_status']] ?? 'secondary';
                    ?>
                    <span class="badge bg-<?php echo $color; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $property['review_status'])); ?>
                    </span>
                </td>
            </tr>
            <?php if ($property['review_date']): ?>
            <tr>
                <td><strong>Reviewed:</strong></td>
                <td><?php echo date('M d, Y H:i', strtotime($property['review_date'])); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($property['reviewer_name']): ?>
            <tr>
                <td><strong>Reviewed By:</strong></td>
                <td><?php echo htmlspecialchars($property['reviewer_name']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="mt-3">
    <h6 class="text-muted">Location Information</h6>
    <table class="table table-sm">
        <tr>
            <td><strong>Address:</strong></td>
            <td><?php echo htmlspecialchars($property['address']); ?></td>
        </tr>
        <tr>
            <td><strong>Location:</strong></td>
            <td><?php echo htmlspecialchars($property['location_name'] ?? 'Not specified'); ?></td>
        </tr>
        <tr>
            <td><strong>Subcity:</strong></td>
            <td><?php echo htmlspecialchars($property['subcity'] ?? 'Not specified'); ?></td>
        </tr>
    </table>
</div>

<?php if ($property['description']): ?>
<div class="mt-3">
    <h6 class="text-muted">Description</h6>
    <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
</div>
<?php endif; ?>

<?php if ($property['amenities']): ?>
<div class="mt-3">
    <h6 class="text-muted">Amenities</h6>
    <p><?php echo nl2br(htmlspecialchars($property['amenities'])); ?></p>
</div>
<?php endif; ?>

<?php if ($property['review_comments']): ?>
<div class="mt-3">
    <h6 class="text-muted">Review Comments</h6>
    <div class="alert alert-info">
        <?php echo nl2br(htmlspecialchars($property['review_comments'])); ?>
    </div>
</div>
<?php endif; ?>

<div class="mt-3">
    <h6 class="text-muted">Property Images</h6>
    <?php
    $images_sql = "SELECT * FROM property_images WHERE property_id = ? ORDER BY is_primary DESC, created_at ASC";
    $images_stmt = $db->prepare($images_sql);
    $images = $db->getMultiple($images_stmt, [$property['property_id']]);
    
    if (empty($images)): ?>
        <p class="text-muted">No images uploaded</p>
    <?php else: ?>
        <div class="row">
            <?php foreach ($images as $image): ?>
                <div class="col-md-4 mb-2">
                    <img src="../uploads/properties/<?php echo htmlspecialchars($image['image_url']); ?>" 
                         alt="Property Image" 
                         class="img-fluid rounded border"
                         style="height: 150px; width: 100%; object-fit: cover;">
                    <?php if ($image['is_primary']): ?>
                        <span class="badge bg-primary">Primary</span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="mt-3">
    <h6 class="text-muted">Owner Information</h6>
    <table class="table table-sm">
        <tr>
            <td><strong>Name:</strong></td>
            <td><?php echo htmlspecialchars($property['owner_name']); ?></td>
        </tr>
        <tr>
            <td><strong>Email:</strong></td>
            <td><?php echo htmlspecialchars($property['owner_email']); ?></td>
        </tr>
        <tr>
            <td><strong>Phone:</strong></td>
            <td><?php echo htmlspecialchars($property['owner_phone'] ?? 'Not provided'); ?></td>
        </tr>
    </table>
</div>

<div class="mt-3">
    <h6 class="text-muted">Submission Information</h6>
    <table class="table table-sm">
        <tr>
            <td><strong>Submitted:</strong></td>
            <td><?php echo date('M d, Y H:i', strtotime($property['created_at'])); ?></td>
        </tr>
        <tr>
            <td><strong>Last Updated:</strong></td>
            <td><?php echo date('M d, Y H:i', strtotime($property['updated_at'])); ?></td>
        </tr>
    </table>
</div>
