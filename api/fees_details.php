<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // actions start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action to update the feee details starts
        if($action=="save_fees"){ 
            try{
                
                $sql ="UPDATE tbl_member_recieved SET date = ?,fees = ?, head = ?, discription = ? WHERE id = ? ";
                
                $parameters = [
                $_POST['date'],
                $_POST['fees'],
                $_POST['head'],
                $_POST['discription'],
                $_POST['id'],
                ];

                $types="ssisi";
                
                app_exec_nonquery($sql, $parameters, $types);
                
            }
            // catch (Throwable $e) {
            //     throw new Exception('Oops! Something went wrong.');
            // }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to update the fee details ends

        // action to delete fee details starts
        // if($action=="delete_fee_details"){
        //     try{
        //         $id = $_POST['id'];
        //         $sql = "DELETE FROM tbl_member_recieved WHERE id = ?";
        //         $types="i";
        //         $parameters = [
        //             $_POST['id'],
        //         ];
    
        //         app_exec_nonquery($sql, $parameters, $types);
    
        //     }
        //     catch (Throwable $e) {
        //         throw new Exception('Oops! Something went wrong.');
        //     }
        // }
        // action to delete fee details ends
         // action to delete recieved amount start
        if($action=="delete_fee_details"){
            $amount=$_POST['amount'];
            try{
                $tm->begin();

                $sql = "DELETE FROM tbl_member_recieved WHERE id = ?";
                $types="i";
                $parameters = [
                $_POST['id'],
                ];
                
                app_exec_roll_back_nonquery($conn,$sql, $parameters, $types);

                $sql = "SELECT p.fees as total_recieveble_Amount,SUM(pd.fees) AS total_recieved_amount
                FROM tbl_member_recievable AS p
                LEFT JOIN tbl_payment_head_master AS g ON p.head = g.id
                LEFT JOIN tbl_member_recieved AS pd ON p.id = pd.receiveble_id where pd.receiveble_id = ?
                GROUP BY p.id, p.date, p.discription, p.fees, p.head, g.type, g.name";
                $parameters = [
                    $_POST['receiveble_id'],
                ];
                $types = "i";
                
                $result = app_exec_getresult($sql, $parameters, $types);

                if (mysqli_num_rows($result) > 0) {
                    // output data of each row
                    while($row = mysqli_fetch_assoc($result)) {
                        $total_recieveble_Amount = (int)$row["total_recieveble_Amount"];
                        $total_recieved_amount = $row["total_recieved_amount"] !== null ? (int)$row["total_recieved_amount"] : 0;
                        $isComplete = ($total_recieveble_Amount - $total_recieved_amount == 0) ? 1 : 0;
                        
                        $sql = "UPDATE tbl_member_recievable SET iscomplete = ?  WHERE id = ?";
                       
                        $parameters = [
                            $isComplete,
                            $_POST['receiveble_id'],
                        ];
                        $types="ii";
                        
                        if (!app_exec_roll_back_nonquery($conn, $sql, $parameters, $types)) {
                            throw new Exception("Error in Updation");
                        }
                    }
                }
                $tm->commit();
                echo 'Deleted Sucessfully';
            }
            catch (Exception $e) {
                $tm->rollback();
                echo $e->getMessage();
            }
        }
        // action to delete recieved amount end

        // action to load fee details of a member starts
        if($action=="load_data"){
            try{
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                $offset = ($current_page - 1) * $rowsPerPage;

                $active_year_start = '2000-04-01';
                $active_yr_res = app_exec_query("SELECT from_year FROM tbl_closing ORDER BY from_year DESC LIMIT 1");
                if ($active_yr_res && $row_yr = $active_yr_res->fetch_assoc()) {
                    $active_year_start = $row_yr['from_year'] . "-04-01";
                }
        
                $sqldatarows ="SELECT m.first_name,m.middle_name,m.last_name,f.date,f.fees,f.id,g.name AS head,f.head AS head_id,f.discription,f.receiveble_id,f.iswallet
                FROM tbl_members AS m
                JOIN tbl_member_recieved AS f ON m.id = f.member_id
                LEFT JOIN tbl_payment_head_master AS g ON f.head = g.id
                WHERE m.id = ? AND f.fees <> 0 AND f.date >= ? ORDER BY f.date DESC";
        
                $sqlcountrows = "SELECT COUNT(*) AS total
                FROM tbl_members AS m
                JOIN tbl_member_recieved AS f ON m.id = f.member_id
                LEFT JOIN tbl_payment_head_master AS g ON f.head = g.id
                WHERE m.id = ? AND f.fees <> 0 AND f.date >= ?";
                
                $sqldatarows_query = $sqldatarows . " LIMIT $offset , $rowsPerPage ";
        
                $result = app_exec_getresult($sqldatarows_query, [(int)$_SESSION['member_id'], $active_year_start], "is");
                    
                $totalRowsResult = app_exec_getresult($sqlcountrows, [(int)$_SESSION['member_id'], $active_year_start], "is");
                $totalRows = $totalRowsResult->fetch_assoc()['total'];
        
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
        
                $pagination = array("total_rows"=>$totalRows);
                $resdata=array($pagination,$qrydata);
        
                echo json_encode($resdata);
                exit();
            }
            // catch (Throwable $e) {
            //     throw new Exception('Oops! Something went wrong.');
            // }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action to load fee details of a member ends

        if($action=="load_heads"){
            try{
                $sql = "SELECT id, name FROM tbl_payment_head_master WHERE type = 'Credit'";
                $result = app_exec_query($sql);

                if ($result && $result->num_rows > 0) {
                    // Fetch all rows as an array
                    $qrydata = [];
                    while ($row = $result->fetch_assoc()) {
                        $qrydata[] = $row; // Add each row to the array
                    }
                } else {
                    $qrydata = []; // If no data is found
                }
        
                // $pagination = array("total_rows"=>$totalRows);
                $resdata=array($qrydata);
        
                echo json_encode($resdata);
                exit();
                
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
    }
?>