<?php
require_once '../../includes/config.php';
$session->requireRole('employee');

header('Content-Type: application/json');

$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($ticket_id <= 0) {
    echo json_encode(['error' => 'Invalid ticket ID']);
    exit;
}

try {
    // Get ticket details with first message
    $sql = "SELECT st.*, u.full_name, u.email, u.phone,
                   e.full_name as assigned_employee_name,
                   (SELECT m.message FROM support_messages m 
                    WHERE m.ticket_id = st.ticket_id 
                    ORDER BY m.created_at ASC LIMIT 1) as message,
                   (SELECT m.message FROM support_messages m 
                    WHERE m.ticket_id = st.ticket_id AND m.sender_role = 'employee' 
                    ORDER BY m.created_at DESC LIMIT 1) as response,
                   (SELECT m.created_at FROM support_messages m 
                    WHERE m.ticket_id = st.ticket_id AND m.sender_role = 'employee' 
                    ORDER BY m.created_at DESC LIMIT 1) as responded_at
            FROM support_tickets st
            JOIN users u ON st.tenant_id = u.user_id
            LEFT JOIN users e ON st.assigned_employee_id = e.user_id
            WHERE st.ticket_id = ?";
    
    $stmt = $db->prepare($sql);
    $ticket = $db->getSingle($stmt, [$ticket_id]);
    
    if (!$ticket) {
        echo json_encode(['error' => 'Ticket not found']);
        exit;
    }
    
    // Add color coding for badges
    $priority_colors = [
        'normal' => 'info',
        'high' => 'warning',
        'urgent' => 'danger'
    ];
    
    $status_colors = [
        'OPEN' => 'warning',
        'IN_PROGRESS' => 'info',
        'CLOSED' => 'success'
    ];
    
    $response = [
        'ticket_id' => $ticket['ticket_id'],
        'subject' => $ticket['subject'],
        'category' => $ticket['category'],
        'priority' => ucfirst($ticket['priority']),
        'priority_color' => $priority_colors[$ticket['priority']] ?? 'secondary',
        'status' => $ticket['status'],
        'status_color' => $status_colors[$ticket['status']] ?? 'secondary',
        'full_name' => $ticket['full_name'],
        'email' => $ticket['email'],
        'phone' => $ticket['phone'],
        'created_at' => date('M d, Y H:i', strtotime($ticket['created_at'])),
        'assigned_employee_name' => $ticket['assigned_employee_name'],
        'message' => $ticket['message'] ?? 'No message',
        'response' => $ticket['response'],
        'responded_at' => $ticket['responded_at'] ? date('M d, Y H:i', strtotime($ticket['responded_at'])) : null
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Get support ticket error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to load ticket details']);
}
?>
