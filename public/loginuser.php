<?php
session_start();
include '../config/koneksi.php';

$error = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $username = mysqli_real_escape_string($koneksi,$_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_query($koneksi,"SELECT * FROM user WHERE username='$username' AND role!='admin' LIMIT 1");
    if($query && mysqli_num_rows($query)>0){
        $data = mysqli_fetch_assoc($query);
        if(password_verify($password,$data['password'])){
            $_SESSION['user_login'] = true;
            $_SESSION['user_username'] = $data['username'];
            $_SESSION['user_nama'] = $data['nama_lengkap'];
            $_SESSION['user_role'] = $data['role'];

            if($data['role']=='penulis'){
                header("Location: penulis/dashboard.php");
            }else{
                header("Location: pembaca/dashboard.php");
            }
            exit;
        }else $error="Password salah!";
    }else $error="Username tidak ditemukan!";
}
?>