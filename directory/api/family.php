<?php
    session_start();
    include '../../app_common/db_connect.php';
    include '../../app_pagination/pagination.php';

    // action start
    if(isset($_POST['action']) && !empty($_POST['action'])) {
        $action = $_POST['action'];

        // action for add family or update family details starts
        if($action=="save_family"){
            try{
                if($_POST['id']==0){

                    $sql ="INSERT INTO tbl_family (name,parent_id,spouse_id,img) VALUES (?, ?, ?, ?)";

                    $types="siis";

                    $parameters = [
                    $_POST['family_name'],
                    $_POST['parent_id'],
                    $_POST['spouse_id'],
                    $_POST['img'],
                    ];
                
                }

                else{
                    $sql = "UPDATE tbl_family SET  name = ?, parent_id = ?, spouse_id = ?, img = ?  WHERE id = ?";

                    
                    $parameters = [
                    $_POST['family_name'],
                    $_POST['parent_id'],
                    $_POST['spouse_id'],
                    $_POST['img'],
                    $_POST['id'],
                    ];

                    $types="siisi";

                }
                    
                app_exec_nonquery($sql, $parameters, $types);
            }
            catch (Exception $e) {
                http_response_code(500);   
                echo "Oops! Something went wrong." . $e->getMessage();
                return;
            }
        }
        // action for add family or update family details end

        // action to load members starts
        if($action=="load_members"){
            try{
                if (isset($_POST['member_val'])){
                    
                    $sql = "SELECT id, first_name,middle_name,last_name,img FROM tbl_members 
                    WHERE first_name LIKE ? OR middle_name LIKE ? OR last_name like ?";

                    $searchvalue= $_POST['member_val'];

                    $parameters = [
                        '%' . $searchvalue . '%',
                        '%' . $searchvalue . '%' ,
                        '%' . $searchvalue . '%'  
                    ];

                    $types="sss";

                    $result = app_exec_getresult($sql,$parameters,$types);

                    if ($result && $result->num_rows > 0) {
                        // Fetch all rows as an array
                        $qrydata = [];
                        while ($row = $result->fetch_assoc()) {
                            $qrydata[] = $row; // Add each row to the array
                        }
                    }
                    else {
                        $qrydata = []; // If no data is found
                    }
                
                    $resdata=array($qrydata);
                
                    echo json_encode($resdata);
                    exit();
                }

            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
           
        }
        // action to load members ends 

        // action to load spouse starts
        if($action=="load_Spouse"){
            try{
                if (isset($_POST['member_val'])){
                    
                    $sql = "SELECT id, first_name,middle_name,last_name,img FROM tbl_members 
                    WHERE first_name LIKE ? OR middle_name LIKE ? OR last_name like ?";

                    $searchvalue= $_POST['member_val'];

                    $parameters = [
                        '%' . $searchvalue . '%',
                        '%' . $searchvalue . '%' ,
                        '%' . $searchvalue . '%'  
                    ];

                    $types="sss";

                    $result = app_exec_getresult($sql,$parameters,$types);

                    if ($result && $result->num_rows > 0) {
                        // Fetch all rows as an array
                        $qrydata = [];
                        while ($row = $result->fetch_assoc()) {
                            $qrydata[] = $row; // Add each row to the array
                        }
                    }
                    else {
                        $qrydata = []; // If no data is found
                    }
                
                    $resdata=array($qrydata);
                
                    echo json_encode($resdata);
                    exit();
                }

            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
           
        }
        // action to load spouse ends

        // action to load memberd starts
        if($action=="loadmembers"){
            try{
                if (isset($_POST['member_val'])){
                    
                    $sql = "SELECT id, first_name,middle_name,last_name FROM tbl_members 
                    WHERE first_name LIKE ? OR middle_name LIKE ? OR last_name like ?";

                    $searchvalue= $_POST['member_val'];

                    $parameters = [
                        '%' . $searchvalue . '%',
                        '%' . $searchvalue . '%' ,
                        '%' . $searchvalue . '%'  
                    ];

                    $types="sss";

                    $result = app_exec_getresult($sql,$parameters,$types);

                    if ($result && $result->num_rows > 0) {
                        // Fetch all rows as an array
                        $qrydata = [];
                        while ($row = $result->fetch_assoc()) {
                            $qrydata[] = $row; // Add each row to the array
                        }
                    }
                    else {
                        $qrydata = []; // If no data is found
                    }
                
                    $resdata=array($qrydata);
                
                    echo json_encode($resdata);
                    exit();
                }

            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
           
        }
        // action to load members ends

        //action add members to family strats
        if($action=="add_member_to_family"){
           
            try{
                if (isset($_POST['id'])) { 

                    $sql = "DELETE FROM tbl_family_member_map WHERE family_id = ?";
                    $parameters = [
                        $_POST['id'],
                    ];
                    $types = "i";
                    $result = app_exec_nonquery($sql, $parameters, $types);

                    $members_ids = $_POST['members_ids']; 
                    $length = count($members_ids);
                    for ($i = 0; $i < $length; $i++) {

                        $current_member_id = $members_ids[$i]; 
                        
                        $sql = "INSERT INTO tbl_family_member_map(family_id, member_id) VALUES (?, ?)";

                        $parameters = [
                            $_POST['id'],
                            $current_member_id,
                        ];

                        $types = "ii";

                        $result = app_exec_nonquery($sql, $parameters, $types);
                    }
                }
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
           
        }
        //action add members to family ends

        //action delete family starts
        if($action=="delete_family"){
            try{
                $id = $_POST['id'];
               
                $sql = "DELETE FROM tbl_family WHERE id = ?";

                $types="i";

                $parameters = [
                $_POST['id'],
                ];
    
                app_exec_nonquery($sql, $parameters, $types);
    
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        //action delete family ends
        
        // action to fetch group details starts
        if($action=="fetch_group_details"){
            try{
                
                if (isset($_POST['family_id'])) {
                    
                    $sql = "SELECT  group_id FROM tbl_family_group_map WHERE family_id=" . $_POST['family_id'];

                    $result = app_exec_query($sql);
                    
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
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        // action to fetch group details ends

        // action to fetch member details starts
        if($action=="fetch_member_details"){
            try{
                
                if (isset($_POST['id'])) {
                    
                    $sql = "SELECT  member_id FROM tbl_family_member_map WHERE family_id=" . $_POST['id'];

                    $result = app_exec_query($sql);
                    
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
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        // action to fetch member details ends

        // action to load groups  starts
        if($action=="load_groups"){
            try{
                $sql = "SELECT id, name,status FROM tbl_family_groups";
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
        // action to load groups ends

        // action add family into group starts
        if($action=="add_family_to_groups"){
           
            try{
                if (isset($_POST['id'])) {

                    $sql = "DELETE FROM tbl_family_group_map WHERE family_id = ?";
                    $parameters = [
                        $_POST['id']
                       
                    ];
                    $types = "i";
                    $result = app_exec_nonquery($sql, $parameters, $types);

                    $group_ids = $_POST['group_ids']; 
                    $length = count($group_ids); 
                    
                    for ($i = 0; $i < $length; $i++) {
                        $current_group_id = $group_ids[$i]; 
                        echo $current_group_id;
                        $sql = "INSERT INTO tbl_family_group_map (family_id, group_id) VALUES (?, ?)";
                        $parameters = [
                            $_POST['id'],
                            $current_group_id,
                        ];
                        $types = "ii";
                        $result = app_exec_nonquery($sql, $parameters, $types);
                    }
                }
            }
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
            
        }
        // action add family into group ends

        // action to load family details starts
        if($action=="load_family_details"){
            try{
                $rowsPerPage = 8;
                // $current_page = (int)$_POST['page'];
                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
        
                // $offset = ($current_page - 1) * $rowsPerPage;
           
               
                    $sqldatarows = "SELECT  id,name,img FROM tbl_family WHERE id = " . $_POST['id']; 
        
                    $sqlcountrows = "SELECT  Count(id) as total
                    FROM tbl_family WHERE id = " . $_POST['id'];
                
                    
        
                // $sqldatarows .= " LIMIT $offset , $rowsPerPage ";
        
                
                    $result = app_exec_query($sqldatarows);
                    
                    $totalRowsResult = app_exec_query($sqlcountrows);
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
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
        }
        // action to load family details ends

        // action load family data starts
        if($action=="load_family_data"){
            try{
                
                $rowsPerPage = 8;
                $current_page = (int)$_POST['page'];

                // Pagination logic
                $sql="";  
                $sqlcountrows="";
                $sqldatarows="";   
                
                $offset = ($current_page - 1) * $rowsPerPage;
                
                if($_POST['val']== ''){
                    $sqldatarows = "SELECT  id, name, parent_id, spouse_id, img FROM tbl_family ";
                     
                    $sqlcountrows = "SELECT  Count(id) as total FROM tbl_family";
                }
                else{            

                    $sqldatarows = "SELECT  id, name, parent_id, spouse_id, img FROM tbl_family WHERE name LIKE ? "; 

                    $sqlcountrows = "SELECT  Count(id) as total FROM tbl_family Where name LIKE ?";         
                }    
                
                $sqldatarows .= " LIMIT $offset , $rowsPerPage ";
            
                if($_POST['val']== '') {
                    $result = app_exec_query($sqldatarows);
                    
                    $totalRowsResult = app_exec_query($sqlcountrows);
                    $totalRows = $totalRowsResult->fetch_assoc()['total'];
                }
                else{
                    $searchvalue= $_POST['val'];
                    $parameters = [
                        '%' . $searchvalue . '%' 
                    ];
                    
                    $types="s";
                    $result=app_exec_getresult($sqldatarows,$parameters,$types);

                    $totalRowsResult = app_exec_getresult($sqlcountrows,$parameters,$types);
                    $totalRows = $totalRowsResult->fetch_assoc()['total'];
                }

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
            catch (Throwable $e) {
                throw new Exception('Oops! Something went wrong.');
            }
            
        }
        // action load family data ends
    }
?>