<?php
require_once '../includes/config.php';

$session->requireRole('owner');

header('Content-Type: application/json');

$owner_id = $session->getUserId();
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? '';
$property_id = $_POST['property_id'] ?? null;

if (!is_numeric($property_id) || !isPropertyOwner($owner_id, (int)$property_id)) {
    $response['message'] = 'Invalid property or access denied';
    echo json_encode($response);
    exit;
}

try {
    switch ($action) {
        case 'resubmit_rejected':
            // Handle resubmission of rejected properties
            $stmt = $db->prepare('SELECT review_status FROM properties WHERE property_id = ? AND owner_id = ?');
            $property = $db->getSingle($stmt, [$property_id, $owner_id]);
            
            if (!$property) {
                $response['message'] = 'Property not found';
                break;
            }
            
            if ($property['review_status'] !== 'rejected') {
                $response['message'] = 'Only rejected properties can be resubmitted';
                break;
            }
            
            // Reset review status and clear rejection data
            $sql = "UPDATE properties 
                    SET review_status = 'pending', 
                        review_comments = NULL, 
                        reviewed_by = NULL, 
                        review_date = NULL, 
                        updated_at = NOW()
                    WHERE property_id = ? AND owner_id = ?";
            
            $stmt = $db->prepare($sql);
            $result = $db->execute($stmt, [$property_id, $owner_id]);
            
            if ($result) {
                // Create notification for employees about resubmission
                try {
                    $property_stmt = $db->prepare('SELECT title FROM properties WHERE property_id = ?');
                    $prop_data = $db->getSingle($property_stmt, [$property_id]);
                    $property_title = $prop_data['title'] ?? 'Unknown Property';
                    
                    $notification_sql = "INSERT INTO notifications (user_id, title, message, type, link, created_at) 
                                       SELECT user_id, 
                                              'Property Resubmitted for Review', 
                                              ?, 
                                              'info', 
                                              CONCAT('/employee/property-review.php?id=', ?), 
                                              NOW()
                                       FROM users 
                                       WHERE role = 'employee'";
                    
                    $notif_stmt = $db->prepare($notification_sql);
                    $message = "Property '{$property_title}' has been resubmitted for review by the owner.";
                    $db->execute($notif_stmt, [$message, $property_id]);
                } catch (Exception $e) {
                    // Notification creation failed, but property was updated
                    error_log('Failed to create resubmission notification: ' . $e->getMessage());
                }
                
                $response['success'] = true;
                $response['message'] = 'Property resubmitted successfully. It will be reviewed by an employee.';
            } else {
                $response['message'] = 'Failed to resubmit property';
            }
            break;
            
        case 'update_property':
            // Handle general property updates
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $property_type = $_POST['property_type'] ?? '';
            $address = trim($_POST['address'] ?? '');
            $location_id = $_POST['location_id'] ?? null;
            $bedrooms = $_POST['bedrooms'] ?? null;
            $bathrooms = $_POST['bathrooms'] ?? null;
            $area_sqm = $_POST['area_sqm'] ?? null;
            $monthly_rent = $_POST['monthly_rent'] ?? null;
            $security_deposit = $_POST['security_deposit'] ?? null;
            $is_furnished = isset($_POST['is_furnished']) ? 1 : 0;
            $amenities = trim($_POST['amenities'] ?? '');
            $featured = isset($_POST['featured']) ? 1 : 0;
            $status = $_POST['status'] ?? 'available';
            
            // Validation
            $errors = [];
            if ($title === '') $errors[] = 'Title is required';
            if ($property_type === '' || !in_array($property_type, ['house', 'apartment', 'villa', 'condominium', 'commercial'])) $errors[] = 'Invalid property type';
            if ($address === '') $errors[] = 'Address is required';
            if (!is_numeric($location_id)) $errors[] = 'Invalid location';
            if (!is_numeric($monthly_rent) || (float)$monthly_rent <= 0) $errors[] = 'Invalid monthly rent';
            if (!in_array($status, ['available', 'maintenance', 'unavailable', 'requested', 'rented'])) $errors[] = 'Invalid status';
            
            if (!empty($errors)) {
                $response['message'] = implode(', ', $errors);
                break;
            }
            
            // Get subcity
            $subcity = null;
            if (is_numeric($location_id)) {
                $loc_stmt = $db->prepare('SELECT subcity FROM locations WHERE location_id = ?');
                $loc = $db->getSingle($loc_stmt, [$location_id]);
                $subcity = $loc['subcity'] ?? null;
            }
            
            // Check if this is a rejected property being updated
            $prop_check = $db->prepare('SELECT review_status FROM properties WHERE property_id = ? AND owner_id = ?');
            $prop_data = $db->getSingle($prop_check, [$property_id, $owner_id]);
            $is_rejected = ($prop_data['review_status'] ?? '') === 'rejected';
            
            // Update property
            $sql = "UPDATE properties
                    SET title = ?, description = ?, property_type = ?, address = ?, location_id = ?, subcity = ?,
                        bedrooms = ?, bathrooms = ?, area_sqm = ?, monthly_rent = ?, security_deposit = ?,
                        is_furnished = ?, amenities = ?, featured = ?, status = ?, updated_at = NOW()";
            
            $params = [
                $title,
                ($description === '' ? null : $description),
                $property_type,
                $address,
                $location_id,
                $subcity,
                ($bedrooms === '' ? null : $bedrooms),
                ($bathrooms === '' ? null : $bathrooms),
                ($area_sqm === '' ? null : $area_sqm),
                $monthly_rent,
                ($security_deposit === '' ? null : $security_deposit),
                $is_furnished,
                ($amenities === '' ? null : $amenities),
                $featured,
                $status
            ];
            
            // If this is a rejected property, reset review status
            if ($is_rejected) {
                $sql .= ", review_status = 'pending', review_comments = NULL, reviewed_by = NULL, review_date = NULL";
            }
            
            $sql .= " WHERE property_id = ? AND owner_id = ?";
            $params[] = $property_id;
            $params[] = $owner_id;
            
            $stmt = $db->prepare($sql);
            $result = $db->execute($stmt, $params);
            
            if ($result) {
                if ($is_rejected) {
                    // Create notification for employees about resubmission
                    try {
                        $notif_sql = "INSERT INTO notifications (user_id, title, message, type, link, created_at) 
                                       SELECT user_id, 
                                              'Property Updated and Resubmitted', 
                                              ?, 
                                              'info', 
                                              CONCAT('/employee/property-review.php?id=', ?), 
                                              NOW()
                                       FROM users 
                                       WHERE role = 'employee'";
                        
                        $notif_stmt = $db->prepare($notif_sql);
                        $message = "Property '{$title}' has been updated and resubmitted for review.";
                        $db->execute($notif_stmt, [$message, $property_id]);
                    } catch (Exception $e) {
                        error_log('Failed to create update notification: ' . $e->getMessage());
                    }
                    
                    $response['success'] = true;
                    $response['message'] = 'Property updated and resubmitted for review successfully.';
                } else {
                    $response['success'] = true;
                    $response['message'] = 'Property updated successfully.';
                }
            } else {
                $response['message'] = 'Failed to update property';
            }
            break;
            
        default:
            $response['message'] = 'Invalid action';
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    error_log('Property update API error: ' . $e->getMessage());
}

echo json_encode($response);
?>
