<?php
session_start();
include '../../app_common/db_connect.php';
include '../../app_common/auth_helper.php';

// actions start
if(isset($_POST['action']) && !empty($_POST['action'])) {
    $action = $_POST['action'];
    $current_login = !empty($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;

    //action load members in a selected group start
    if($action=="load_member_data"){
        try{
            $group_id = (int)$_POST['val'];
            if (!hasGroupAccess($current_login, $group_id)) {
                echo json_encode([[]]);
                exit();
            }

            $rowsPerPage = 8;
            $current_page = (int)$_POST['page'];
            // Pagination logic
            $sql="";  
            $sqlcountrows="";
            $sqldatarows="";   
    
            $offset = ($current_page - 1) * $rowsPerPage;
           
            $sqldatarows = "SELECT m.first_name,m.middle_name,m.last_name,m.id
            FROM tbl_members AS m JOIN tbl_group_member_map AS gmm ON m.id = gmm.member_id
            WHERE gmm.group_id = ? ORDER BY m.first_name,m.middle_name,m.last_name";
            
            $result = app_exec_getresult($sqldatarows, [$group_id], "i");
                
            //stringify
            if ($result && $result->num_rows > 0) {
                // Fetch all rows as an array
                $qrydata = [];
                while ($row = $result->fetch_assoc()) {
                    $qrydata[] = $row; // Add each row to the array
                }
            } else {
                $qrydata = []; // If no data is found
            }
    
            $resdata=array($qrydata);
    
            echo json_encode($resdata);
            exit();
        }
        catch (Throwable $e) {
            throw new Exception('Oops! Something went wrong.');
        }
    }
    //action load members in a selected group end

    // action to load groups for load members start
    if($action=="load_groups"){
        try{
            $allowed = getUserAllowedGroupIds($current_login);
            if (in_array('ALL', $allowed, true)) {
                $sql = "SELECT id, name FROM tbl_groups WHERE status = 1 ORDER BY name ASC";
                $result = app_exec_query($sql);
            } else if (!empty($allowed)) {
                $placeholders = implode(',', array_fill(0, count($allowed), '?'));
                $types = str_repeat('i', count($allowed));
                $sql = "SELECT id, name FROM tbl_groups WHERE id IN ($placeholders) AND status = 1 ORDER BY name ASC";
                $result = app_exec_getresult($sql, $allowed, $types);
            } else {
                $result = null;
            }

            if ($result && $result->num_rows > 0) {
                // Fetch all rows as an array
                $qrydata = [];
                while ($row = $result->fetch_assoc()) {
                    $qrydata[] = $row; // Add each row to the array
                }
            } else {
                $qrydata = []; // If no data is found
            }
    
            $resdata=array($qrydata);
    
            echo json_encode($resdata);
            exit();
            
        }
        catch (Throwable $e) {
            throw new Exception('Oops! Something went wrong.');
        }
    }
    // action to load groups for load members end

    //action to add attendance of slected members start
    if($action=="add_attendance"){

        try{
            $tm->begin();
            if (isset($_POST['val'])) {

                $sql = "DELETE FROM tbl_attendance WHERE date = ? AND group_id = ?";

                $parameters = [
                    $_POST['date'],
                    $_POST['val'],
                ];
                $types = "si";

                // Saving Query as text file start
                $folder = "Queries";
                // Create the folder if it doesn't exist
                if (!is_dir($folder)) {
                    mkdir($folder, 0777, true);
                }
                // Set file name with current date and time
                $dateTime = date('Y-m-d_H-i-s'); // Example: 2025-04-28_14-30-15
                $filename = "$folder/query_$dateTime.txt";
                // Save query string to the file
                file_put_contents($filename, $sql);
                // Saving Query as  text file end

                if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                    throw new Exception("Error inserting into other recieveble");
                }
                // $result = app_exec_nonquery($sql, $parameters, $types);

                $member_ids = $_POST['member_ids']; 
                $length = count($member_ids); 
                
                for ($i = 0; $i < $length; $i++) {

                    $current_member_ids = $member_ids[$i]; 

                    $sql = "INSERT INTO tbl_attendance (member_id,group_id,date) VALUES (?, ?, ?)";

                    $parameters = [
                        $current_member_ids,
                        $_POST['val'],
                        $_POST['date'],

                    ];

                    $types = "iis";

                    // Saving Query as text file start
                    $folder = "Queries";
                    // Create the folder if it doesn't exist
                    if (!is_dir($folder)) {
                        mkdir($folder, 0777, true);
                    }
                    // Set file name with current date and time
                    date_default_timezone_set('Asia/Kolkata');
                    $dateTime = date('Y-m-d_H-i-s'); // Example: 2025-04-28_14-30-15
                    $filename = "$folder/query_$dateTime.txt";
                    // Save query string to the file
                    file_put_contents($filename, $sql);
                    // Saving Query as  text file end


                    // $result = app_exec_nonquery($sql, $parameters, $types);
                    if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                        throw new Exception("Error inserting into other recieved");
                    }

                    $tm->commit();
                    echo "Transaction Successful!";
                }
            }
        }
        // catch (Throwable $e) {
        //     $conn->rollback();
        //     throw new Exception('Oops! Something went wrong.');
        // }
        catch (Exception $e) {
                
            $tm->rollback();
            
            echo $e->getMessage();
            
        }
        
    }
    //action to add attendance of selected members end

    //action to fetch the attendance details of a given date start
    if($action=="fetch_Attendance_details"){
        try{
            
            if (isset($_POST['date'])) {

                $sql = "SELECT  member_id FROM tbl_attendance WHERE group_id= ? AND date = ?";

                $searchvalue= $_POST['date'];

                $parameters = [
                    $_POST['group'],
                    $searchvalue,
                ];
                
                $types="is";

                $result=app_exec_getresult($sql,$parameters,$types);

            }
            if ($result && $result->num_rows > 0) {
                // Fetch all rows as an array
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row; // Add each row to the array
                }
            } else {
                $data = []; // If no data is found
            }
            
            echo json_encode($data);
            exit();
            
        }
        catch (Exception $e) {
            http_response_code(500);   
            echo "Oops! Something went wrong." . $e->getMessage();
            return;
        }
    }
    // action to fetch the attendance details of a given date end
}

?>