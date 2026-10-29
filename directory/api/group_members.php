<?php
require_once __DIR__ . '/api_bootstrap.php';

include_once __DIR__ . '/../../app_pagination/pagination.php';

$login_id = (int)($_SESSION['login_id'] ?? 0);

// actions start
if (isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];

    // action create and add member to group
    if ($action == "create_and_add_member") {
        header('Content-Type: application/json');
        try {
            if (empty($login_id)) {
                http_response_code(401);
                echo json_encode(['success' => false, 'Message' => 'Session expired. Please log in again.']);
                exit();
            }

            $first_name = trim($_POST['first_name'] ?? '');
            $middle_name = trim($_POST['middle_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $gender = trim($_POST['gender'] ?? 'Male');
            $father_name = trim($_POST['father_name'] ?? '');
            $mother_name = trim($_POST['mother_name'] ?? '');
            $dob = trim($_POST['dob'] ?? '');
            $blood_group = (int)($_POST['blood_group'] ?? 0);
            $phone = trim($_POST['phone'] ?? '');
            $whtsapp = trim($_POST['whtsapp'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $p_street = trim($_POST['p_street'] ?? '');
            $p_city = trim($_POST['p_city'] ?? '');
            $p_pincode = trim($_POST['p_pincode'] ?? '');
            $p_country = trim($_POST['p_country'] ?? '');
            $img = trim($_POST['img'] ?? '0');
            $inactive = (int)($_POST['inactive'] ?? 0);
            $member_type = (int)($_POST['member_type'] ?? 0);
            $group_id = (int)($_POST['group_id'] ?? 0);

            if (empty($first_name)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'Message' => 'First Name is required.']);
                exit();
            }

            if (empty($last_name)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'Message' => 'Last Name is required.']);
                exit();
            }

            if (empty($phone)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'Message' => 'Phone number is required.']);
                exit();
            }

            if ($group_id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'Message' => 'Please select a valid group.']);
                exit();
            }

            if (!hasGroupAccess($login_id, $group_id)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'Message' => 'You do not have permission to add members to this group.']);
                exit();
            }

            if (!empty($email)) {
                $chk_res = app_exec_getresult("SELECT id FROM tbl_members WHERE email = ?", [$email], "s");
                if ($chk_res && $chk_res->num_rows > 0) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'Message' => 'This email address is already in use by another member.']);
                    exit();
                }
            }

            $sql = "INSERT INTO tbl_members (first_name, middle_name, last_name, gender, father_name, mother_name, dob, blood_group, phone, whtsapp, email, p_street, p_city, p_pincode, p_country, img, inactive, member_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $parameters = [
                $first_name,
                $middle_name,
                $last_name,
                $gender,
                $father_name,
                $mother_name,
                !empty($dob) ? $dob : '1990-01-01',
                $blood_group,
                $phone,
                !empty($whtsapp) ? $whtsapp : $phone,
                $email,
                $p_street,
                $p_city,
                $p_pincode,
                $p_country,
                !empty($img) ? $img : '0',
                $inactive,
                $member_type
            ];
            $types = "sssssssissssisssii";

            $new_member_id = app_exec_getlast_id($sql, $parameters, $types);

            if ($new_member_id && $new_member_id > 0) {
                // Insert into group map
                app_exec_nonquery("INSERT INTO tbl_group_member_map (member_id, group_id) VALUES (?, ?)", [$new_member_id, $group_id], "ii");

                echo json_encode(['success' => true, 'Message' => 'Member created and added to group successfully.', 'id' => $new_member_id]);
                exit();
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'Message' => 'Failed to create member record.']);
                exit();
            }
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'Message' => 'Error: ' . $e->getMessage()]);
            exit();
        }
    }

    // action search existing members not in group
    if ($action == "search_existing_members") {
        header('Content-Type: application/json');
        try {
            if (empty($login_id)) {
                http_response_code(401);
                echo json_encode(['success' => false, 'Message' => 'Session expired. Please log in again.']);
                exit();
            }

            $group_id = (int)($_POST['group_id'] ?? 0);
            $query = trim($_POST['query'] ?? '');

            if ($group_id <= 0) {
                echo json_encode(['success' => true, 'data' => []]);
                exit();
            }

            if (!hasGroupAccess($login_id, $group_id)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'Message' => 'Permission denied.']);
                exit();
            }

            $sql = "SELECT DISTINCT m.id, m.first_name, m.middle_name, m.last_name, m.email, m.phone, m.img, m.member_type
                    FROM tbl_members m
                    WHERE m.inactive = 0
                      AND m.id NOT IN (SELECT member_id FROM tbl_group_member_map WHERE group_id = ?)";
            $params = [$group_id];
            $types = "i";

            if (!empty($query)) {
                $sql .= " AND (m.first_name LIKE ? OR m.last_name LIKE ? OR m.phone LIKE ? OR m.email LIKE ?)";
                $q = '%' . $query . '%';
                $params[] = $q;
                $params[] = $q;
                $params[] = $q;
                $params[] = $q;
                $types .= "ssss";
            }

            $sql .= " ORDER BY m.first_name, m.last_name LIMIT 20";

            $res = app_exec_getresult($sql, $params, $types);
            $members = [];
            if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                    $members[] = $row;
                }
            }

            echo json_encode(['success' => true, 'data' => $members]);
            exit();
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'Message' => 'Error: ' . $e->getMessage()]);
            exit();
        }
    }

    // action add existing member to group
    if ($action == "add_existing_member_to_group") {
        header('Content-Type: application/json');
        try {
            if (empty($login_id)) {
                http_response_code(401);
                echo json_encode(['success' => false, 'Message' => 'Session expired. Please log in again.']);
                exit();
            }

            $member_id = (int)($_POST['member_id'] ?? 0);
            $group_id = (int)($_POST['group_id'] ?? 0);

            if ($member_id <= 0 || $group_id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'Message' => 'Invalid member or group selection.']);
                exit();
            }

            if (!hasGroupAccess($login_id, $group_id)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'Message' => 'You do not have permission to modify this group.']);
                exit();
            }

            // Check if already mapped
            $chk = app_exec_getresult("SELECT id FROM tbl_group_member_map WHERE member_id = ? AND group_id = ?", [$member_id, $group_id], "ii");
            if ($chk && $chk->num_rows > 0) {
                echo json_encode(['success' => true, 'Message' => 'Member is already in this group.']);
                exit();
            }

            app_exec_nonquery("INSERT INTO tbl_group_member_map (member_id, group_id) VALUES (?, ?)", [$member_id, $group_id], "ii");

            echo json_encode(['success' => true, 'Message' => 'Member added to group successfully.']);
            exit();
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'Message' => 'Error: ' . $e->getMessage()]);
            exit();
        }
    }

    // action remove member from group
    if ($action == "remove_member") {
        header('Content-Type: application/json');
        try {
            if (empty($login_id)) {
                http_response_code(401);
                echo json_encode(['success' => false, 'Message' => 'Session expired. Please log in again.']);
                exit();
            }

            $member_id = (int)($_POST['id'] ?? 0);
            $group_id = (int)($_POST['group_id'] ?? ($_SESSION['id'] ?? 0));

            if ($member_id > 0 && $group_id > 0) {
                if (!hasGroupAccess($login_id, $group_id)) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'Message' => 'Permission denied.']);
                    exit();
                }

                $sql = "DELETE FROM tbl_group_member_map WHERE member_id = ? AND group_id = ?";
                app_exec_nonquery($sql, [$member_id, $group_id], "ii");
            }
            
            echo json_encode(['success' => true, 'Message' => 'Member removed from group.']);
            exit();
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'Message' => 'Oops! Something went wrong.']);
            exit();
        }
    }

    // action to save fees of a member
    if ($action == "save_fees") {
        try {
            $sql = "INSERT INTO tbl_member_recieved (member_id, fees, date, login_id, head, discription) VALUES (?, ?, ?, ?, ?, ?)";
            $parameters = [
                $_POST['member_id'],
                $_POST['fees'],
                $_POST['date'],
                $_SESSION['login_id'],
                $_POST['head'],
                $_POST['discription'],
            ];
            $types = "iisiss";
            app_exec_nonquery($sql, $parameters, $types);
        } catch (Throwable $e) {
            throw new Exception('Oops! Something went wrong.');
        }
    }

    // action load group members data
    if ($action == "load_data") {
        try {
            $rowsPerPage = 8;
            $current_page = (int)$_POST['page'];
            $sql = "";  
            $sqlcountrows = "";
            $sqldatarows = "";   

            $offset = ($current_page - 1) * $rowsPerPage;

            $target_group_id = (int)($_POST['group_id'] ?? ($_SESSION['selected_group_id'] ?? 0));

            if ($_POST['val'] == '') {
                $sqldatarows = "SELECT m.first_name,m.middle_name,m.last_name,m.id,m.email,m.phone,whtsapp,img
                FROM tbl_members AS m JOIN tbl_group_member_map AS gmm ON m.id = gmm.member_id
                WHERE gmm.group_id = ? LIMIT ?, ?";  

                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_members m
                JOIN tbl_group_member_map gmm ON m.id = gmm.member_id WHERE gmm.group_id = ?";

                $result = app_exec_getresult($sqldatarows, [$target_group_id, $offset, $rowsPerPage], "iii");
                $totalRowsResult = app_exec_getresult($sqlcountrows, [$target_group_id], "i");
                $totalRows = ($totalRowsResult && $tr = $totalRowsResult->fetch_assoc()) ? $tr['total'] : 0;
            } else {            
                $searchvalue = '%' . $_POST['val'] . '%';
                $sqldatarows = "SELECT m.first_name,m.middle_name,m.last_name,m.id,m.email,m.phone,whtsapp,img
                FROM tbl_members AS m JOIN tbl_group_member_map AS gmm ON m.id = gmm.member_id
                WHERE first_name LIKE ? AND gmm.group_id = ? LIMIT ?, ?";

                $sqlcountrows = "SELECT COUNT(*) AS total FROM tbl_members m
                JOIN tbl_group_member_map gmm ON m.id = gmm.member_id WHERE first_name LIKE ? AND gmm.group_id = ?";

                $result = app_exec_getresult($sqldatarows, [$searchvalue, $target_group_id, $offset, $rowsPerPage], "siii");
                $totalRowsResult = app_exec_getresult($sqlcountrows, [$searchvalue, $target_group_id], "si");
                $totalRows = ($totalRowsResult && $tr = $totalRowsResult->fetch_assoc()) ? $tr['total'] : 0;
            }

            if ($result && $result->num_rows > 0) {
                $qrydata = [];
                while ($row = $result->fetch_assoc()) {
                    $qrydata[] = $row;
                }
            } else {
                $qrydata = [];
            }

            $pagination = array("total_rows" => $totalRows);
            $resdata = array($pagination, $qrydata);

            echo json_encode($resdata);
            exit();
        } catch (Throwable $e) {
            throw new Exception('Oops! Something went wrong.');
        }
    }
}
?>