<?php
include "../config/config.inc.php";

class Atividade {
    private $id;
    private $descricao;
    private $peso;
    private $anexo;
    private $data;
    private $arquivo;

    public function __construct($id, $desc, $peso, $anexo, $data, $arquivo = null) {
        $this->id = $id;
        $this->descricao = $desc;
        $this->peso = $peso;
        $this->anexo = $anexo;
        $this->data = $data;
        $this->arquivo = $arquivo;
    }

    public function setId($id){
        if ($id < 0)
            throw new Exception("Erro, a ID deve ser maior que 0!");
        else
            $this->id = $id;
    }

    public function setDescricao($desc){
        if ($desc == "")
            throw new Exception("Erro, a descrição deve ser informada!");
        else
            $this->descricao = $desc;
    }

    public function setPeso($peso){
        if ($peso < 0)
            throw new Exception("Erro, o peso deve ser maior que 0!");
        else
            $this->peso = $peso;
    }

    public function setAnexo($anexo = ''){
        $this->anexo = $anexo;
    }

    public function setData($data){
        $this->data = $data;
    }

    public function setArquivo($arquivo){
        $this->arquivo = $arquivo;
    }

    public function getId(): int { return $this->id; }
    public function getDescricao(): string { return $this->descricao; }
    public function getPeso(): float { return $this->peso; }
    public function getAnexo(): string { return $this->anexo; }
    public function getData(): string { return $this->data; }
    public function getArquivo(): string { return $this->arquivo; }

    public function __toString(): string {
        return "Atividade: $this->id - $this->descricao - Peso: $this->peso - Anexo: $this->anexo - Data: $this->data - Arquivo: $this->arquivo";
    }

    public function inserir(): bool {
        $conexao = new PDO(DSN, USUARIO, SENHA);
        $sql = "INSERT INTO atividade (descricao, peso, anexo, data, arquivo)
                VALUES (:descricao, :peso, :anexo, :data, :arquivo)";
        $comando = $conexao->prepare($sql);
        $comando->bindValue(':descricao', $this->getDescricao());
        $comando->bindValue(':peso', $this->getPeso());
        $comando->bindValue(':anexo', $this->getAnexo());
        $comando->bindValue(':data', $this->data);
        $comando->bindValue(':arquivo', $this->arquivo);
        return $comando->execute();
    }

    public static function listar(): array {
        $conexao = new PDO(DSN, USUARIO, SENHA);
        $sql = "SELECT * FROM atividade ORDER BY data DESC";
        $comando = $conexao->prepare($sql);
        $comando->execute();
        return $comando->fetchAll();
    }

    public function alterar(): bool {
        $conexao = new PDO(DSN, USUARIO, SENHA);
        $sql = "UPDATE atividade SET descricao = :descricao, peso = :peso, anexo = :anexo, data = :data, arquivo = :arquivo
                WHERE id = :id";
        $comando = $conexao->prepare($sql);
        $comando->bindValue(':descricao', $this->descricao);
        $comando->bindValue(':peso', $this->peso);
        $comando->bindValue(':anexo', $this->anexo);
        $comando->bindValue(':data', $this->data);
        $comando->bindValue(':arquivo', $this->arquivo);
        $comando->bindValue(':id', $this->id);
        return $comando->execute();
    }
}
?>
