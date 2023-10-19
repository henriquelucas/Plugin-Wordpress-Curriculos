<?php
/*
Plugin Name: Curriculo Simples
Description: Recebe currculos em seu Wordpress com esse Plugin Simples e intuitivo.
Version: 1.0
Author: Henrique Lucas de Sousa
*/

if (isset($_POST['backup_dados'])) {
    exportar_dados();
}

if (isset($_POST['importar_dados'])) {
    importar_dados();
}

// Função para criar a tabela no banco de dados do WordPress
function criar_tabela_candidatos() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $tabela = $wpdb->prefix . 'candidatos';

    $sql = "CREATE TABLE $tabela (
        id INT NOT NULL AUTO_INCREMENT,
        nome_completo VARCHAR(255) NOT NULL,
        cpf VARCHAR(14) NOT NULL,
        data_nascimento DATE NOT NULL,
        telefone VARCHAR(15) NOT NULL,
        email VARCHAR(100) NOT NULL,
        endereco TEXT NOT NULL,
        municipio VARCHAR(100) NOT NULL,
        experiencias_profissionais TEXT,
        foto VARCHAR(255),
        curriculo VARCHAR(255),
        cargo_id INT,
        status VARCHAR(50) NOT NULL DEFAULT 'Banco de Talentos',
        PRIMARY KEY (id)
    ) $charset_collate;";    

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'criar_tabela_candidatos');


// Função para criar a tabela de cargos no banco de dados
function criar_tabela_cargos() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $tabela = $wpdb->prefix . 'cargos';

    $sql = "CREATE TABLE $tabela (
        id INT NOT NULL AUTO_INCREMENT,
        nome_cargo VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";    

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'criar_tabela_cargos');

function incluir_bootstrap_no_backend() {
    // Verifica se você está no painel de administração
    if (is_admin()) {
        // Inclui o Bootstrap CSS do CDN
        wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css');
    }
}
add_action('admin_enqueue_scripts', 'incluir_bootstrap_no_backend');


// Função para adicionar o formulário de cadastro na página
function formulario_cadastro_curriculo() {
    ob_start();
    if (isset($_POST['submit'])) {
        $nome_completo = sanitize_text_field($_POST['nome_completo']);
        $cpf = sanitize_text_field($_POST['cpf']);
        $data_nascimento = sanitize_text_field($_POST['data_nascimento']);
        $telefone = sanitize_text_field($_POST['telefone']);
        $email = sanitize_email($_POST['email']);
        $endereco = sanitize_text_field($_POST['endereco']);
        $municipio = sanitize_text_field($_POST['municipio']);
        $experiencias_profissionais = sanitize_text_field($_POST['experiencias_profissionais']);
        $status = sanitize_text_field($_POST['status']);

        // Processar o upload da foto
        $foto = '';
        if ($_FILES['foto']['size'] > 0) {
            $upload = wp_upload_bits($_FILES['foto']['name'], null, file_get_contents($_FILES['foto']['tmp_name']));
            if (empty($upload['error'])) {
                $foto = $upload['url'];
            }
        }

        // Processar o upload do currículo
        $curriculo = '';
        if ($_FILES['curriculo']['size'] > 0) {
            $upload = wp_upload_bits($_FILES['curriculo']['name'], null, file_get_contents($_FILES['curriculo']['tmp_name']));
            if (empty($upload['error'])) {
                $curriculo = $upload['url'];
            }
        }

        global $wpdb;
        $tabela = $wpdb->prefix . 'candidatos';
        $wpdb->insert(
            $tabela,
            array(
                'nome_completo' => $nome_completo,
                'cpf' => $cpf,
                'data_nascimento' => $data_nascimento,
                'telefone' => $telefone,
                'email' => $email,
                'endereco' => $endereco,
                'municipio' => $municipio,
                'experiencias_profissionais' => $experiencias_profissionais,
                'foto' => $foto,
                'curriculo' => $curriculo,
                'status' => $status,
            )
        );

        echo '<div class="alert alert-success">Currículo cadastrado com sucesso!</div>';
    }
    ?>
    <form method="post" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="nome_completo">Nome Completo:</label>
            <input type="text" class="form-control" name="nome_completo" id="nome_completo" required>
        </div>
        <div class="form-group">
            <label for="cpf">CPF:</label>
            <input type="text" class="form-control" name="cpf" id="cpf" required>
        </div>
        <div class="form-group">
            <label for="data_nascimento">Data de Nascimento:</label>
            <input type="date" class="form-control" name="data_nascimento" id="data_nascimento" required>
        </div>
        <div class="form-group">
            <label for="telefone">Telefone de Contato:</label>
            <input type="tel" class="form-control" name="telefone" id="telefone" required>
        </div>
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" class="form-control" name="email" id="email" required>
        </div>
        <div class="form-group">
            <label for="endereco">Endereço:</label>
            <input type="text" class="form-control" name="endereco" id="endereco" required>
        </div>
        <div class="form-group">
            <label for="municipio">Município:</label>
            <input type="text" class="form-control" name="municipio" id="municipio" required>
        </div>
        <div class="form-group">
            <label for="experiencias_profissionais">Experiências Profissionais:</label>
            <textarea class="form-control" name="experiencias_profissionais" id="experiencias_profissionais" rows="4"></textarea>
        </div>
        
        <div class="form-group">
            <label for="foto">Upload de Foto:</label>
            <input type="file" name="foto" id="foto">
        </div>
        <div class="form-group">
            <label for="curriculo">Upload de Currículo:</label>
            <input type="file" name="curriculo" id="curriculo">
        </div>
        <input type="submit" name="submit" value="Cadastrar Currículo" class="btn btn-primary">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('cadastro-curriculo', 'formulario_cadastro_curriculo');

// Função para adicionar um submenu no menu "Currículos"
function adicionar_submenu_curriculos() {
    add_menu_page(
        'Currículos',                // Título da página
        'Currículos',                // Texto do menu
        'manage_options',            // Capacidade necessária para acessar
        'gerenciamento-candidatos',  // Slug da página
        'pagina_gerenciamento'       // Função que renderiza a página
    );

    // Adicione o submenu "Cargos" sob o menu "Currículos"
    add_submenu_page(
        'gerenciamento-candidatos',  // Slug do menu pai
        'Cargos',                    // Título da página de cargos
        'Cargos',                    // Texto do submenu
        'manage_options',            // Capacidade necessária para acessar
        'gerenciamento-cargos',      // Slug da página de cargos
        'pagina_cargos'              // Função que renderiza a página de gerenciamento de cargos
    );

    // Adicione o submenu "Gerenciar Cargos" sob o menu "Currículos"
    add_submenu_page(
        'gerenciamento-candidatos',  // Slug do menu pai
        'Gerenciar Cargos',          // Título da página de gerenciamento de cargos
        'Gerenciar Cargos',          // Texto do submenu
        'manage_options',            // Capacidade necessária para acessar
        'gerenciar-cargos',          // Slug da página de gerenciamento de cargos
        'pagina_gerenciamento_cargos' // Função que renderiza a página de gerenciamento de cargos
    );

    // Adicione o submenu "Cargos" sob o menu "Currículos"
    add_submenu_page(
        'gerenciamento-candidatos', // Página pai (aqui, a página "Currículos")
        'Dashboard',
        'Configurações',
        'manage_options',
        'dashboard',
        'pagina_dashboard'
    );

    add_submenu_page(
        'gerenciamento-candidatos',
        'Opções de Backup',
        'Opções de Backup',
        'manage_options',
        'pagina_opcoes_backup',
        'pagina_opcoes_backup'
    );
 
}
add_action('admin_menu', 'adicionar_submenu_curriculos');

function pagina_dashboard() {
    // Seu código para exibir informações do currículo aqui
    echo '<div class="wrap">';
    echo '<h2>Configurações</h2>';

    echo '<p>Obrigado por instalar nossso Curriculo Simples ao seu Wordpress! Esperamos que essa ferramenta possa ajudar você no
    seu dia a como recebimentos de curriculos para sua instituição.</p>';
    echo '<h6><b>Shortcode</b></h6>';
    echo '<p>Para exibir o formulario de preenchimento do curriculo no Frontend: <b>[cadastro-curriculo-frontend]<b></p>';
    // Adicione aqui o código para exibir informações do currículo
    echo '</div>';
}

// Função para exportar os dados
function exportar_dados() {
    global $wpdb;
    $tabela = $wpdb->prefix . 'candidatos';

    $dados = $wpdb->get_results("SELECT * FROM $tabela");

    // Converter os dados em formato JSON
    $dados_json = json_encode($dados);

    // Definir os cabeçalhos de resposta para o download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename=backup-candidatos.json');

    // Saída do JSON
    echo $dados_json;
    exit;
}



function importar_dados() {
    if (isset($_FILES['arquivo_backup']) && $_FILES['arquivo_backup']['error'] === UPLOAD_ERR_OK) {
        // Caminho temporário do arquivo enviado
        $temp_file = $_FILES['arquivo_backup']['tmp_name'];

        // Conteúdo do arquivo
        $dados_json = file_get_contents($temp_file);

        if (!empty($dados_json)) {
            global $wpdb;
            $tabela = $wpdb->prefix . 'candidatos';

            // Converter o JSON de volta em um array de dados
            $dados = json_decode($dados_json);

            if (is_array($dados) && !empty($dados)) {
                // Limpar a tabela atual de candidatos
                $wpdb->query("TRUNCATE TABLE $tabela");

                // Inserir os dados de volta na tabela
                foreach ($dados as $dado) {
                    $wpdb->insert($tabela, (array) $dado);
                }
            }
        }
    }
}


// Pagina do Backup
function pagina_opcoes_backup() {
    echo '<div class="wrap">';
    echo '<h2 class="mt-3">Opções de Backup</h2>';

    echo '<form method="post" action="">';
    echo '<h6>Faça o backup dos seus dados:</h6>';
    echo '<input type="submit" name="backup_dados" class="button button-primary mb-3" value="Fazer Backup">';
    echo '</form>';

    echo '<form method="post" action="" enctype="multipart/form-data">'; // Adicione enctype="multipart/form-data"
echo '<input type="file" name="arquivo_backup">'; // Campo de upload
echo '<input type="submit" name="importar_dados" class="button" value="Importar Dados">';
echo '</form>';


    echo '</div>';
}

// Função para renderizar a página de gerenciamento de currículos
function pagina_gerenciamento() {
    global $wpdb;
    $tabela = $wpdb->prefix . 'candidatos';
    $cargos_tabela = $wpdb->prefix . 'cargos';

    // Recuperar todos os cargos disponíveis
    $cargos = $wpdb->get_results("SELECT id, nome_cargo FROM $cargos_tabela");

    // Inicializar o ID do cargo para filtrar (todos os candidatos inicialmente)
    $cargo_filtrado = '';

    // Verificar se o formulário de filtro foi submetido
    if (isset($_POST['filtrar_cargo'])) {
        $cargo_filtrado = intval($_POST['cargo_selecionado']);
    }

    // Construir a consulta SQL com base no cargo filtrado
    $query = "SELECT * FROM $tabela";
    
    if (!empty($cargo_filtrado)) {
        $query .= $wpdb->prepare(" WHERE cargo_id = %d", $cargo_filtrado);
    }

    $candidatos = $wpdb->get_results($query);

    echo '<div class="wrap">';
    echo '<h2>Gerenciamento de Currículos</h2>';

    // Formulário de filtro por cargo
    echo '<form method="post" action="">';
    echo '<div class="form-group mb-5">';
    echo '<label for="cargo_selecionado">Filtrar por Cargo:</label>';
    echo '<select name="cargo_selecionado" id="cargo_selecionado" class="form-control">';
    echo '<option value="">Todos os Cargos</option>';

    foreach ($cargos as $cargo) {
        $selected = ($cargo->id == $cargo_filtrado) ? 'selected' : '';
        echo '<option value="' . esc_attr($cargo->id) . '" ' . $selected . '>' . esc_html($cargo->nome_cargo) . '</option>';
    }

    echo '</select>';
    echo '<input type="submit" name="filtrar_cargo" value="Filtrar" class="btn btn-primary mt-3">';
    echo '</div>';
    echo '</form>';

    if ($candidatos) {
        echo '<table class="widefat">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Nome Completo</th>';
        echo '<th>CPF</th>';
        echo '<th>Data de Nascimento</th>';
        echo '<th>Email</th>';
        echo '<th>Ações</th>'; // Nova coluna para as ações
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($candidatos as $candidato) {
            $data_formatada = date('d-m-Y', strtotime($candidato->data_nascimento));
            echo '<tr>';
            echo '<td>' . esc_html($candidato->id) . '</td>';
            echo '<td>' . esc_html($candidato->nome_completo) . '</td>';
            echo '<td>' . esc_html($candidato->cpf) . '</td>';
            echo '<td>' . esc_html($data_formatada) . '</td>';
            echo '<td>' . esc_html($candidato->email) . '</td>';
            echo '<td>
                    <form method="post">
                        <input type="hidden" name="candidato_id" value="' . $candidato->id . '">
                        <button type="submit" name="excluir_candidato" class="button">Excluir</button>
                    </form>
                </td>';

            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>Nenhum currículo encontrado.</p>';
    }

    echo '</div>';
}

// Lógica para excluir candidatos
if (isset($_POST['excluir_candidato'])) {
    $candidato_id = intval($_POST['candidato_id']);
    // Certifique-se de validar e verificar se o candidato com o ID especificado existe

    global $wpdb;
    $tabela = $wpdb->prefix . 'candidatos';
    $wpdb->delete($tabela, array('id' => $candidato_id));
}


// Função para renderizar a página de gerenciamento de cargos
function pagina_gerenciamento_cargos() {
    ob_start();

    global $wpdb;

    // Processar o envio do formulário de cadastro de cargos
    if (isset($_POST['cadastrar_cargo'])) {
        $cargo = sanitize_text_field($_POST['cargo']);

        // Certifique-se de validar os dados, como verificar se o campo não está vazio

        if (!empty($cargo)) {
            // Agora, insira o cargo na tabela de cargos
            $tabela_cargos = $wpdb->prefix . 'cargos';
            $wpdb->insert(
                $tabela_cargos,
                array('nome_cargo' => $cargo)
            );

            echo '<div class="updated"><p>Cargo cadastrado com sucesso!</p></div>';
        } else {
            echo '<div class="error"><p>O campo de cargo não pode estar vazio.</p></div>';
        }
    }

    // Exibir o formulário de cadastro de cargos
    ?>
    <div class="wrap">
        <h2>Cadastro de Cargos</h2>
        <form method="post" action="">
            <div class="form-group">
                <label for="cargo">Cargo:</label>
                <input type="text" class="form-control" name="cargo" id="cargo" required>
            </div>
            <input type="submit" name="cadastrar_cargo" value="Cadastrar Cargo" class="mt-3 button button-primary">
        </form>
    </div>
    <?php

    echo ob_get_clean();
}


// Função para listar os cargos na página de gerenciamento de cargos
function pagina_cargos() {
    global $wpdb;
    $tabela = $wpdb->prefix . 'cargos';
    
    if (isset($_GET['delete_cargo'])) {
        $cargo_id = intval($_GET['delete_cargo']);
        
        // Certifique-se de validar $cargo_id e verificar se o cargo existe
        if ($cargo_id) {
            $wpdb->delete($tabela, array('id' => $cargo_id));
            echo '<div class="updated"><p>Cargo excluído com sucesso!</p></div>';
        }
    }

    $cargos = $wpdb->get_results("SELECT * FROM $tabela");

    ob_start();
    
    echo '<div class="wrap">';
    echo '<h2>Gerenciamento de Cargos</h2>';

    if ($cargos) {
        echo '<table class="widefat">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Nome do Cargo</th>';
        echo '<th>Ações</th>'; // Adicione uma coluna para as ações (exclusão)
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($cargos as $cargo) {
            echo '<tr>';
            echo '<td>' . esc_html($cargo->id) . '</td>';
            echo '<td>' . esc_html($cargo->nome_cargo) . '</td>';
            echo '<td><a href="?page=gerenciamento-cargos&delete_cargo=' . $cargo->id . '">Excluir</a></td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>Nenhum cargo cadastrado.</p>';
    }

    echo '</div>';

    echo ob_get_clean();
}



// Função para criar um shortcode que exibe o formulário de cadastro no frontend
function formulario_cadastro_curriculo_frontend() {
    ob_start();

     // Recupere os cargos do banco de dados
     global $wpdb;
     $tabela_cargos = $wpdb->prefix . 'cargos';
     $cargos = $wpdb->get_results("SELECT id, nome_cargo FROM $tabela_cargos");

    if (isset($_POST['submit'])) {
        $nome_completo = sanitize_text_field($_POST['nome_completo']);
        $cpf = sanitize_text_field($_POST['cpf']);
        $data_nascimento = sanitize_text_field($_POST['data_nascimento']);
        $telefone = sanitize_text_field($_POST['telefone']);
        $email = sanitize_email($_POST['email']);
        $endereco = sanitize_text_field($_POST['endereco']);
        $municipio = sanitize_text_field($_POST['municipio']);
        $experiencias_profissionais = sanitize_textarea_field($_POST['experiencias_profissionais']);
        $foto = sanitize_text_field($_POST['foto']);
        $curriculo = sanitize_text_field($_POST['curriculo']);
        $cargo_selecionado = intval($_POST['cargo_selecionado']); // Certifique-se de que seja um número válido
    
        // Insira os dados do candidato na tabela de candidatos
        $tabela_candidatos = $wpdb->prefix . 'candidatos';
        $wpdb->insert(
            $tabela_candidatos,
            array(
                'nome_completo' => $nome_completo,
                'cpf' => $cpf,
                'data_nascimento' => $data_nascimento,
                'telefone' => $telefone,
                'email' => $email,
                'endereco' => $endereco,
                'municipio' => $municipio,
                'experiencias_profissionais' => $experiencias_profissionais,
                'foto' => $foto,
                'curriculo' => $curriculo,
                'cargo_id' => $cargo_selecionado, // Inserir o ID do cargo
                'status' => 'Banco de Talentos', // Pode definir o status padrão aqui
            )
        );
    
        // Exibir uma mensagem de sucesso
        echo '<div class="alert alert-success">Currículo cadastrado com sucesso!</div>';
    }
    ?>

    <form method="post" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="nome_completo">Nome Completo:</label>
            <input type="text" class="form-control" name="nome_completo" id="nome_completo" required>
        </div>
        <div class="form-group">
            <label for="cpf">CPF:</label>
            <input type="text" class="form-control" name="cpf" id="cpf" required>
        </div>
        <div class="form-group">
            <label for="data_nascimento">Data de Nascimento:</label>
            <input type="date" class="form-control" name="data_nascimento" id="data_nascimento" required>
        </div>
        <div class="form-group">
            <label for="telefone">Telefone de Contato:</label>
            <input type="tel" class="form-control" name="telefone" id="telefone" required>
        </div>
        <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" class="form-control" name="email" id="email" required>
        </div>
        <div class="form-group">
            <label for="endereco">Endereço:</label>
            <input type="text" class="form-control" name="endereco" id="endereco" required>
        </div>
        <div class="form-group">
            <label for="municipio">Município:</label>
            <input type="text" class="form-control" name="municipio" id="municipio" required>
        </div>

        <div class="form-group">
            <label for="cargo_selecionado">Vagas disponiveis:</label>
            <select name="cargo_selecionado" id="cargo_selecionado" class="form-control" required>
                <option value="">Selecione um cargo</option>
                <?php
                // Loop para exibir as opções de cargos
                foreach ($cargos as $cargo) {
                    echo '<option value="' . esc_attr($cargo->id) . '">' . esc_html($cargo->nome_cargo) . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="experiencias_profissionais">Experiências Profissionais:</label>
            <textarea class="form-control" name="experiencias_profissionais" id="experiencias_profissionais" rows="4"></textarea>
        </div>
        <div class="form-group">
            <label for="foto">Upload de Foto:</label>
            <input type="file" name="foto" id="foto">
        </div>
        <div class="form-group">
            <label for="curriculo">Upload de Currículo:</label>
            <input type="file" name="curriculo" id="curriculo">
        </div>
        <input type="submit" name="submit" value="Cadastrar Currículo" class="btn btn-primary">
    </form>

    <?php
    return ob_get_clean();
}
add_shortcode('cadastro-curriculo-frontend', 'formulario_cadastro_curriculo_frontend');
