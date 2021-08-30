<?php
class Sql extends PDO
{
    private $conexao;

    public function __construct()
    {			
		$this->conexao =  new PDO("mysql:dbname=dbemp00339;host=bd2.tixtelecom.com.br", "cliente_s", '>M%9PRg")f8p)qMZ');
    }

    private function setParam($query, $key, $value)
    {
        $query->bindParam($key, $value);
    }

    private function setParams($query, $parametros = array())
    {
        foreach ($parametros as $key => $value) {
            $this->setParam($query, $key, $value);
        }
    }

    public function query($rawQuery, $parametros = array())
    {
        $query = $this->conexao->prepare($rawQuery);
        $this->setParams($query, $parametros);
        $query->execute();
        return $query;
    }

    public function select($rawQuery, $parametros = array())
    {
        $resultado = $this->query($rawQuery, $parametros);
        return $resultado->fetchAll(PDO::FETCH_ASSOC);
    }

}

class Api extends Sql {

    public function all($cpf)
    {
        $participa = $this->select("
            SELECT 
                p.tx_id AS cpf, p.email,
            CASE
                WHEN frt.expiration_date < DATE_FORMAT(now(), '%Y-%m-%d') THEN 0
                WHEN frt.expiration_date >= DATE_FORMAT(now(), '%Y-%m-%d') THEN 1
            END participa
            FROM 
                people p
            LEFT JOIN financial_receivable_titles frt ON frt.client_id = p.id
            LEFT JOIN contracts c ON c.id = frt.contract_id 
            WHERE 
                frt.p_is_receivable = 1
                AND frt.bill_title_id IS NULL
                AND frt.`type` = 2
                AND frt.deleted = 0
                AND frt.finished = 0
                AND c.status NOT IN (4,9) && c.stage = 3
                AND p.tx_id = :TX_ID
            GROUP BY p.id
        ", array(':TX_ID'=>$cpf));
        
        $participa[0]['n_contratos'] = $this->select("
            SELECT 
                p.tx_id AS cpf,
                count(c.id) AS n_contratos
            FROM 
                people p
            LEFT JOIN contracts c ON c.client_id = p.id
            WHERE 
                c.status NOT IN (4,9) && c.stage = 3
                AND p.tx_id = :TX_ID
            GROUP BY p.id;
        ", array(':TX_ID'=>$cpf))[0]['n_contratos'];
        
        $participa[0]['n_faturas'] = $this->select("
            SELECT 
                p.tx_id AS cpf,
                count(frt.title) AS n_faturas
            FROM 
                people p
            LEFT JOIN financial_receivable_titles frt ON frt.client_id = p.id
            LEFT JOIN contracts c ON c.id = frt.contract_id 
            WHERE 
                frt.p_is_receivable = 1
                AND frt.bill_title_id IS NULL
                AND frt.`type` = 2
                AND frt.deleted = 0
                AND frt.finished = 0
                AND frt.expiration_date <= DATE_FORMAT(now(), '%Y-%m-%d')
                AND c.status NOT IN (4,9) && c.stage = 3
                AND p.tx_id = :TX_ID
            GROUP BY p.id;
        ", array(':TX_ID'=>$cpf))[0]['n_faturas'];

        return json_encode($participa[0]);
    }

    public function participa($cpf)
    {
        $result = $this->select("
            SELECT 
                p.tx_id AS cpf, p.email,
            CASE
                WHEN frt.expiration_date < DATE_FORMAT(now(), '%Y-%m-%d') THEN 0
                WHEN frt.expiration_date >= DATE_FORMAT(now(), '%Y-%m-%d') THEN 1
            END participa
            FROM 
                people p
            LEFT JOIN financial_receivable_titles frt ON frt.client_id = p.id
            LEFT JOIN contracts c ON c.id = frt.contract_id 
            WHERE 
                frt.p_is_receivable = 1
                AND frt.bill_title_id IS NULL
                AND frt.`type` = 2
                AND frt.deleted = 0
                AND frt.finished = 0
                AND c.status NOT IN (4,9) && c.stage = 3
                AND p.tx_id = :TX_ID
            GROUP BY p.id
        ", array(':TX_ID'=>$cpf));

        return json_encode($result[0]);
    }

    public function contratos($cpf)
    {
        $result = $this->select("
            SELECT 
                p.tx_id AS cpf,
                count(c.id) AS n_contratos
            FROM 
                people p
            LEFT JOIN contracts c ON c.client_id = p.id
            WHERE 
                c.status NOT IN (4,9) && c.stage = 3
                AND p.tx_id = :TX_ID
            GROUP BY p.id;
        ", array(':TX_ID'=>$cpf));

        return json_encode($result[0]);
    }

    public function faturas($cpf)
    {
        $result = $this->select("
            SELECT 
                p.tx_id AS cpf,
                count(frt.title) AS n_faturas
            FROM 
                people p
            LEFT JOIN financial_receivable_titles frt ON frt.client_id = p.id
            LEFT JOIN contracts c ON c.id = frt.contract_id 
            WHERE 
                frt.p_is_receivable = 1
                AND frt.bill_title_id IS NULL
                AND frt.`type` = 2
                AND frt.deleted = 0
                AND frt.finished = 0
                AND frt.expiration_date <= DATE_FORMAT(now(), '%Y-%m-%d')
                AND c.status NOT IN (4,9) && c.stage = 3
                AND p.tx_id = :TX_ID
            GROUP BY p.id;
        ", array(':TX_ID'=>$cpf));

        return json_encode($result[0]);
    }
}