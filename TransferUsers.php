<?php


class TransferUsers
{
    /* Loyalty DB credentials*/
    const DB_HOST = "127.0.0.1";
    const DB_USERNAME = "root";
    const DB_PASSWORD = '1';
    const DB_DATABASE = "a2takeit";
    /*------------------------*/

    const PASSWORD_LENGTH = 15;
    const INPUT_DIR = 'input';
    const OUTPUT_DIR = 'output';
    const OUTPUT_FILE = 'loteria_users.csv';

    public $csvOutputArr = [["id", "email", "password", "initial_sc"]]; //headers for csv output file
    public $filearr = [];
    public $conn;


    public function __construct($filearr)
    {
        $this->conn = new mysqli(self::DB_HOST, self::DB_USERNAME, self::DB_PASSWORD, self::DB_DATABASE);
        $this->conn->query("SET NAMES 'utf8'");
        $this->conn->query("SET character_set_client='utf8'");
        $this->conn->query("SET character_set_connection='utf8'");
        $this->conn->query("SET character_set_results='utf8'");
        $this->conn->query("SET character_set_server='utf8'");
        $this->filearr = $filearr;
    }

    public function start()
    {
        foreach ($this->filearr as $file) {
            $data = $this->readCsv($file);
            if ($data) {
                foreach ($data as $dt) {
                    //Check if user has been already registered in Loyalty
                    if (!$this->isUserEmailExists($dt['email'])) {
                        $res = $this->saveDBTableUser($dt);
                        if ($res) {
                            $createdUserId = $this->conn->insert_id;
                            $this->savaDBTableFan($createdUserId);
                            $this->savaDBTablePersonalNumberHistory($createdUserId);
                            $this->savaDBTableUserDataHistory($dt);
                        }
                    }
                }

            } else echo "NO CSV data";
        }
        $this->saveCSVtoFile($this->csvOutputArr);
    }

    /**
     * Read CSV file containing lottery user's data
     * @param $file
     * @return array|false
     */
    public function readCsv($file)
    {
        $out = [];
        $row = 1;
        $file_path = self::INPUT_DIR . DIRECTORY_SEPARATOR . $file . ".csv";
        if (($handle = fopen($file_path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                $row++;
                $arr['email'] = $data['1'];
                $arr['full_name'] = $data['2'];
                $arr['zip'] = $data['3'];
                $arr['phone'] = $data['4'];
                $arr['initial_sc'] = $data['6'];
                array_push($out, $arr);
            }
            array_shift($out);
            fclose($handle);

            return $out;
        } else return false;
    }

    /**
     * Save created user's data to DB (table user)
     * @param $data
     * @return bool|mysqli_result
     */
    public function saveDBTableUser($data)
    {
        $password = $this->randomPassword();
        $password_hash = md5('2ti' . md5($password));
        $email_hash = sha1($data['email']);
        echo "User added: " . $data['email'] . PHP_EOL;
        $sql = "
            INSERT INTO `user` SET
            email = '" . $data['email'] . "',
            contact_email = '" . $data['email'] . "',
            contact_email_confirmed = 0,
            password = '" . $password_hash . "',
            full_name = '" . $data['full_name'] . "',
            age = -3,
            access_token='',
            is_admin = 0,
            is_superadmin = 0,
            is_banned = 0,
            is_b2b_accepted = 0,
            created_at = NOW(),
            can_do_contests = 1,
            email_hash = '" . $email_hash . "',
            has_logged_in = 0,
            registration_method = 'Email',
            is_demo = 0,
            sync_counter = 0,
            tmp_repaired = 0,
            email_confirmed = 0,
            updated_at = NOW(),
            message_add = 1,
            message_rate = 1,
            is_archivised = 0,
            recomendation_user_id = 0,
            time_over_remind_sent = 0
            
    ";
        $result = $this->conn->query($sql) or die($this->conn->error);
        if ($result) {
            $data['password'] = $password;
            $this->addOutputCSVData($data);
        }

        return $result;

    }

    /**
     * Save created user's data to DB (table fan)
     * @param $userId
     * @return bool|mysqli_result
     */
    public function savaDBTableFan($userId)
    {
        $sql = "
                INSERT INTO `fan` SET
                company_id  = 378,
                user_id = '" . $userId . "',
                is_loyalty = 1,
                `time` = NOW(),
                time_backup = NOW(),
                `size` = 0,
                cnt = 1,
                recomendation_user_id = 0,
                recomendation_gamification_id = 0,
                has_logged_in = 0,
                is_newsletter = 0,
                money = 0,
                can_notify = 0,
                seen_help = 0,
                entry_gamification_id = 0,
                sent_final_sms =0,
                entry_point = 'NONE',
                entry_source = 'app',
                event_id = 0,
                unseen_points = 10,
                new_user = 0,
                new_user_vue = 1,
                is_blocked = 0,
                entry_referer = ''

        ";
        $result = $this->conn->query($sql) or die($this->conn->error);

        return $result;

    }

    /**
     * Save created user's data to DB (table personal_number_history)
     * @param $userId
     * @return bool|mysqli_result
     */
    public function savaDBTablePersonalNumberHistory($userId)
    {
        $sql = "
            INSERT INTO `personal_number_history` SET
            company_id  = 378,
            user_id = '" . $userId . "',
            personal_number = 0,
            money = 10,
            delta = 10,
            reason = 'Otrzymujesz niespodziankę 10p od CHA Piaseczno',
            `type` = 'RE',
            foreign_id = 0,
            gamification_id = 0,
            partner_company_id = 0

    ";
        $result = $this->conn->query($sql) or die($this->conn->error);

        return $result;

    }

    /**
     * Save created user's data to DB (table user_data_history)
     * @param $data
     * @return bool|mysqli_result
     */
    public function savaDBTableUserDataHistory($data)
    {
        $sql = "
            INSERT INTO `user_data_history` SET
            user_id = 0,
            old_email = '" . $data['email'] . "',
            `date` = CURDATE() 

    ";
        $result = $this->conn->query($sql) or die($this->conn->error);

        return $result;

    }

    /**
     * Push created user's data to the common array
     * @param $data
     */
    public function addOutputCSVData($data)
    {
        $csv_data = [];
        $csv_data['id'] = count($this->csvOutputArr);
        $csv_data['email'] = $data['email'];
        $csv_data['password'] = $data['password'];
        $csv_data['initial_sc'] = $data['initial_sc'];
        array_push($this->csvOutputArr, $csv_data);

    }

    /**
     * Save transferred user's data array to csv file
     * @param $csvarr
     */
    public function saveCSVtoFile($csvarr)
    {

        if (!file_exists(self::OUTPUT_DIR)) {
            mkdir(self::OUTPUT_DIR, 0777, true);
        }

        $fp = fopen(self::OUTPUT_DIR . DIRECTORY_SEPARATOR . self::OUTPUT_FILE, 'w');
        foreach ($csvarr as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
        echo "File " . self::OUTPUT_DIR . DIRECTORY_SEPARATOR . self::OUTPUT_FILE . " created" . PHP_EOL;
    }

    /**
     * Check if user has been already registered in Loyalty DB
     * @param $email
     * @return bool
     */
    public function isUserEmailExists($email)
    {
        $sql = "SELECT COUNT(email) as cnt
          FROM `user` 
          WHERE email='" . $email . "' 
          ";
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        if ($row['cnt'] == 0)
            return false;
        return true;
    }

    /**
     * Generate random password
     * @return string
     */
    public function randomPassword()
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < self::PASSWORD_LENGTH; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }


}