<?php
require_once "Atividade.class.php";

$conexao = new PDO(DSN, USUARIO, SENHA);

// Upload do arquivo
$nomeArquivo = null;
if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] == 0) {
    $pasta = "upload/";
    if (!is_dir($pasta)) {
        mkdir($pasta);
    }
    $nomeTemporario = $_FILES['arquivo']['tmp_name'];
    $nomeOriginal = basename($_FILES['arquivo']['name']);
    $nomeArquivo = uniqid() . "_" . preg_replace('/[^a-zA-Z0-9\._-]/', '_', $nomeOriginal);
    move_uploaded_file($nomeTemporario, $pasta . $nomeArquivo);
}

// Excluir atividade
if (isset($_GET['excluir'])) {
    $idExcluir = $_GET['excluir'];
    $sql = "DELETE FROM atividade WHERE id = :id";
    $comando = $conexao->prepare($sql);
    $comando->bindValue(':id', $idExcluir);
    $comando->execute();
    header("Location: atividade.php");
    exit;
}

// Preparar para edição
$atividadeParaEditar = null;
if (isset($_GET['editar'])) {
    $idEditar = $_GET['editar'];
    $sql = "SELECT * FROM atividade WHERE id = :id";
    $comando = $conexao->prepare($sql);
    $comando->bindValue(':id', $idEditar);
    $comando->execute();
    $atividadeParaEditar = $comando->fetch(PDO::FETCH_ASSOC);
}

// Salvar nova atividade ou atualizar existente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $descricao = $_POST['descricao'];
    $valor = $_POST['peso'];
    $modalidade = $_POST['anexo'];
    $data = $_POST['data'];

    // Se fez upload de novo arquivo, usa ele; senão mantém arquivo antigo (se editar)
    if ($nomeArquivo) {
        $arquivo = $nomeArquivo;
    } elseif (!empty($_POST['arquivo_antigo'])) {
        $arquivo = $_POST['arquivo_antigo'];
    } else {
        $arquivo = null;
    }

    if ($id) {
        // Atualizar
        $sql = "UPDATE atividade SET descricao = :descricao, peso = :peso, anexo = :anexo, data = :data, arquivo = :arquivo WHERE id = :id";
        $comando = $conexao->prepare($sql);
        $comando->bindValue(':id', $id);
    } else {
        // Inserir
        $sql = "INSERT INTO atividade (descricao, peso, anexo, data, arquivo) VALUES (:descricao, :peso, :anexo, :data, :arquivo)";
        $comando = $conexao->prepare($sql);
    }

    $comando->bindValue(':descricao', $descricao);
    $comando->bindValue(':peso', $valor);
    $comando->bindValue(':anexo', $modalidade);
    $comando->bindValue(':data', $data);
    $comando->bindValue(':arquivo', $arquivo);

    $comando->execute();

    header("Location: atividade.php");
    exit;
}

// Filtros
$filtroDescricao = $_GET['filtro_descricao'] ?? '';
$filtroModalidade = $_GET['filtro_modalidade'] ?? '';
$filtroData = $_GET['filtro_data'] ?? '';

$atividades = array_filter(Atividade::listar(), function ($atividade) use ($filtroDescricao, $filtroModalidade, $filtroData) {
    $cond = true;
    if ($filtroDescricao !== '') {
        $cond = $cond && $atividade['descricao'] === $filtroDescricao;
    }
    if ($filtroModalidade !== '') {
        $cond = $cond && $atividade['anexo'] === $filtroModalidade;
    }
    if ($filtroData !== '') {
        $cond = $cond && $atividade['data'] === $filtroData;
    }
    return $cond;
});
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Controle Financeiro</title>
</head>
<body>
    <h1>Manutenção de Atividades Financeiras</h1>
    <form action="atividade.php" method="post" enctype="multipart/form-data">
        <fieldset>
            <legend>Formulário</legend>

            <!-- Campo oculto para id -->
            <input type="hidden" name="id" value="<?= htmlspecialchars($atividadeParaEditar['id'] ?? '') ?>">
            <!-- Mantém arquivo antigo -->
            <input type="hidden" name="arquivo_antigo" value="<?= htmlspecialchars($atividadeParaEditar['arquivo'] ?? '') ?>">

            <label for="descricao">Descrição:</label>
            <select name="descricao" required>
                <option value="">Selecione</option>
                <option value="Gasto" <?= (isset($atividadeParaEditar) && $atividadeParaEditar['descricao'] == 'Gasto') ? 'selected' : '' ?>>Gasto</option>
                <option value="Ganho" <?= (isset($atividadeParaEditar) && $atividadeParaEditar['descricao'] == 'Ganho') ? 'selected' : '' ?>>Ganho</option>
            </select>

            <label for="peso">Valor:</label>
            <input type="number" name="peso" min="0" step="0.01" required
                value="<?= htmlspecialchars($atividadeParaEditar['peso'] ?? '') ?>">

            <label for="anexo">Modalidade:</label>
            <select name="anexo" required>
                <option value="">Selecione</option>
                <?php
                $modalidades = ["Alimentação","Transporte","Moradia","Educação","Saúde","Lazer","Compras","Contas","Outros"];
                foreach ($modalidades as $modal) {
                    $selected = (isset($atividadeParaEditar) && $atividadeParaEditar['anexo'] === $modal) ? 'selected' : '';
                    echo "<option value=\"$modal\" $selected>$modal</option>";
                }
                ?>
            </select>

            <label for="data">Data:</label>
            <input type="date" name="data" required
                value="<?= htmlspecialchars($atividadeParaEditar['data'] ?? '') ?>">

            <label for="arquivo">Arquivo:</label>
            <input type="file" name="arquivo" accept=".pdf,.jpg,.png">

            <button type="submit"><?= isset($atividadeParaEditar) ? 'Atualizar' : 'Salvar' ?></button>
        </fieldset>
    </form>

    <h2>Filtros</h2>
    <form method="get">
        <label for="filtro_descricao">Descrição:</label>
        <select name="filtro_descricao">
            <option value="">Todos</option>
            <option value="Gasto" <?= $filtroDescricao == 'Gasto' ? 'selected' : '' ?>>Gasto</option>
            <option value="Ganho" <?= $filtroDescricao == 'Ganho' ? 'selected' : '' ?>>Ganho</option>
        </select>

        <label for="filtro_modalidade">Modalidade:</label>
        <select name="filtro_modalidade">
            <option value="">Todas</option>
            <?php
            foreach ($modalidades as $modal) {
                $selected = ($filtroModalidade === $modal) ? 'selected' : '';
                echo "<option value=\"$modal\" $selected>$modal</option>";
            }
            ?>
        </select>

        <label for="filtro_data">Data:</label>
        <input type="date" name="filtro_data" value="<?= htmlspecialchars($filtroData) ?>">

        <button type="submit">Filtrar</button>
    </form>

    <h2>Listagem de Atividades</h2>
    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th>
            <th>Descrição</th>
            <th>Valor</th>
            <th>Modalidade</th>
            <th>Data</th>
            <th>Arquivo</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($atividades as $atividade): ?>
            <tr>
                <td><?= htmlspecialchars($atividade['id']) ?></td>
                <td><?= htmlspecialchars($atividade['descricao']) ?></td>
                <td>R$ <?= number_format($atividade['peso'], 2, ',', '.') ?></td>
                <td><?= htmlspecialchars($atividade['anexo']) ?></td>
                <td><?= htmlspecialchars($atividade['data']) ?></td>
                <td>
                    <?php if (!empty($atividade['arquivo'])): ?>
                        <a href="upload/<?= htmlspecialchars($atividade['arquivo']) ?>" download>Baixar</a>
                    <?php else: ?>
                        Sem arquivo
                    <?php endif; ?>
                </td>
                <td>
                    <a href="atividade.php?editar=<?= htmlspecialchars($atividade['id']) ?>">Editar</a> |
                    <a href="atividade.php?excluir=<?= htmlspecialchars($atividade['id']) ?>" onclick="return confirm('Tem certeza que deseja excluir?')">Excluir</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
