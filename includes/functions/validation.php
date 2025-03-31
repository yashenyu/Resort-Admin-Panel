<?php
/**
 * Validation Functions
 * 
 * Functions for validating user input
 */

/**
 * Validate required fields
 * 
 * @param array $required_fields Array of field names to check
 * @param array $data Data to validate against
 * @return array Array of missing fields
 */
function validate_required_fields($required_fields, $data) {
    $missing = [];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            $missing[] = $field;
        }
    }
    
    return $missing;
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool Whether email is valid
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate date format
 * 
 * @param string $date Date to validate
 * @param string $format Format to validate against (default Y-m-d)
 * @return bool Whether date is valid
 */
function validate_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate numeric value
 * 
 * @param mixed $value Value to validate
 * @return bool Whether value is numeric
 */
function validate_numeric($value) {
    return is_numeric($value);
}

/**
 * Validate integer value
 * 
 * @param mixed $value Value to validate
 * @return bool Whether value is an integer
 */
function validate_integer($value) {
    return filter_var($value, FILTER_VALIDATE_INT) !== false;
}

/**
 * Validate date range
 * 
 * @param string $start_date Start date
 * @param string $end_date End date
 * @return bool Whether end date is after start date
 */
function validate_date_range($start_date, $end_date) {
    $start = strtotime($start_date);
    $end = strtotime($end_date);
    
    return $start && $end && $end > $start;
}

/**
 * Validate booking dates
 * 
 * @param string $check_in Check-in date
 * @param string $check_out Check-out date
 * @param int $room_id Room ID to check availability for
 * @param int $booking_id Current booking ID (optional, for updates)
 * @return array Array of errors, if any
 */
function validate_booking_dates($check_in, $check_out, $room_id, $booking_id = null) {
    global $conn;
    
    $errors = [];
    
    // Validate date formats
    if (!validate_date($check_in) || !validate_date($check_out)) {
        $errors[] = "Invalid date format. Please use YYYY-MM-DD.";
        return $errors;
    }
    
    // Validate date range
    if (!validate_date_range($check_in, $check_out)) {
        $errors[] = "Check-out date must be after check-in date.";
        return $errors;
    }
    
    // Check if dates are in the past
    $today = date('Y-m-d');
    if (strtotime($check_in) < strtotime($today)) {
        $errors[] = "Check-in date cannot be in the past.";
    }
    
    // Check if the room is available for these dates
    $query = "
        SELECT booking_id 
        FROM bookings 
        WHERE room_id = $room_id 
        AND status = 'Confirmed'
        AND (
            (check_in_date <= '$check_in' AND check_out_date >= '$check_in') OR
            (check_in_date <= '$check_out' AND check_out_date >= '$check_out') OR
            (check_in_date >= '$check_in' AND check_out_date <= '$check_out')
        )
    ";
    
    // Exclude current booking if updating
    if ($booking_id) {
        $query .= " AND booking_id != $booking_id";
    }
    
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "The room is not available for the selected dates.";
    }
    
    return $errors;
}

/**
 * Validate form data and return errors
 * 
 * @param array $data Form data to validate
 * @param array $rules Validation rules
 * @return array Array of validation errors
 */
function validate_form($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule_set) {
        foreach ($rule_set as $rule) {
            // Skip if field is not required and is empty
            if ($rule !== 'required' && !isset($data[$field]) || trim($data[$field]) === '') {
                continue;
            }
            
            switch ($rule) {
                case 'required':
                    if (!isset($data[$field]) || trim($data[$field]) === '') {
                        $errors[$field] = ucfirst($field) . " is required.";
                    }
                    break;
                    
                case 'email':
                    if (!validate_email($data[$field])) {
                        $errors[$field] = "Please enter a valid email address.";
                    }
                    break;
                    
                case 'numeric':
                    if (!validate_numeric($data[$field])) {
                        $errors[$field] = ucfirst($field) . " must be a number.";
                    }
                    break;
                    
                case 'integer':
                    if (!validate_integer($data[$field])) {
                        $errors[$field] = ucfirst($field) . " must be an integer.";
                    }
                    break;
                    
                case 'date':
                    if (!validate_date($data[$field])) {
                        $errors[$field] = "Please enter a valid date (YYYY-MM-DD).";
                    }
                    break;
                    
                default:
                    // Handle custom validation rules
                    if (strpos($rule, 'min:') === 0) {
                        $min = substr($rule, 4);
                        if (strlen($data[$field]) < $min) {
                            $errors[$field] = ucfirst($field) . " must be at least $min characters.";
                        }
                    } elseif (strpos($rule, 'max:') === 0) {
                        $max = substr($rule, 4);
                        if (strlen($data[$field]) > $max) {
                            $errors[$field] = ucfirst($field) . " cannot exceed $max characters.";
                        }
                    }
                    break;
            }
            
            // Stop validating this field if there's already an error
            if (isset($errors[$field])) {
                break;
            }
        }
    }
    
    return $errors;
}
