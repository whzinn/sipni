<?php
header('Content-Type: text/html; charset=utf-8');
require 'get_bearer.php';
$config = file_get_contents('config.json');
$config = json_decode($config, True);
if(!isset($_GET['consulta'])){
    exit;
}

$cpf = trim($_GET['consulta']);
$cpf = str_replace(" ", "", $cpf);
$cpf = str_replace("-", "", $cpf);
$cpf = str_replace("_", "", $cpf);
$cpf = str_replace(".", "", $cpf);
$cpf = str_replace(",", "", $cpf);

if($_GET['consulta'] == NULL){
    die("⚠️ Por favor digite um CPF.");
}
if(strlen($cpf) > 11 OR strlen($cpf) < 11){
    die('⚠️ Por favor digite um CPF válido.');
}

$bearer_token = $config['sipni_token'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://servicos-cloud.saude.gov.br/pni-bff/v1/cidadao/cpf/'.$cpf.'');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_ENCODING, "gzip");
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
'User-Agent: Mozilla/5.0 (Windows NT '. rand(11, 99) .'.0; Win64; x64) AppleWebKit/'. rand(111, 991) .'.'. rand(11, 99) .' (KHTML, like Gecko) Chrome/'. rand(11, 99) .'.0.0.0 Safari/537.36',
'Authorization: Bearer '.$bearer_token.'',
'DNT: 1',
'Referer: https://si-pni.saude.gov.br/',
));
$re = curl_exec($ch);
$parsed = json_decode($re, True);

if(stripos($re, 'Token do usuário do SCPA inválido/expirado') OR stripos($re, 'Não autorizado') OR stripos($re, 'Unauthorized')){
    $config['sipni_token'] = get_bearer_sipni();
    $config = json_encode($config);
    file_put_contents('config.json', $config);
    header('Location: ' . $_SERVER['PHP_SELF'].'?consulta='.$cpf.'');
}

if($parsed['records'] == []){
    die('⚠️ CPF não encontrado.');
}

if($parsed['code'] == 200){
    echo('👤 Dados Pessoais'.'<br>'.'<br>');
    echo('⤬ CPF: '.$parsed['records'][0]['cpf'].''.'<br>');
    echo('⤬ CNS: '.$parsed['records'][0]['cnsDefinitivo'].''.'<br>'.'<br>');
    echo('⤬ Nome: '.$parsed['records'][0]['nome'].''.'<br>');
    $nascimento = explode('-',$parsed['records']['0']['dataNascimento']);
    echo('⤬ Nascimento: '.$nascimento[2].'/'.$nascimento[1].'/'.$nascimento[0].''.'<br>');
    $idade = date('Y') - $nascimento[0];
    echo('⤬ Idade: '.$idade.''.'<br>');
    if($parsed['records'][0]['sexo'] == 'M'){
        $sexo = 'Masculino';
    }
    if($parsed['records'][0]['sexo'] == 'F'){
        $sexo = 'Feminino';
    }
    if(isset($parsed['records'][0]['grauQualidade'])){
        $grauDeQualidade = $parsed['records'][0]['grauQualidade'];
    }else{
        $grauDeQualidade = 'Sem informação';
    }
    echo('⤬ Grau de Qualidade: '.$grauDeQualidade.''.'<br>');
    echo('⤬ Gênero: '.$sexo.''.'<br>');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://servicos-cloud.saude.gov.br/pni-bff/v1/municipio/'.$parsed['records'][0]['nacionalidade']['municipioNascimento'].'');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'User-Agent: Mozilla/5.0 (Windows NT '. rand(11, 99) .'.0; Win64; x64) AppleWebKit/'. rand(111, 991) .'.'. rand(11, 99) .' (KHTML, like Gecko) Chrome/'. rand(11, 99) .'.0.0.0 Safari/537.36',
    'Authorization: Bearer '.$bearer_token.'',
    'DNT: 1',
    'Referer: https://si-pni.saude.gov.br/',
    ));
    $re_tres = curl_exec($ch);
    $parsed_tres = json_decode($re_tres, True);

    if(isset($parsed_tres['record']['nome'])){
        $muni_nasc = $parsed_tres['record']['nome'];
    }else{
        $muni_nasc = "Sem informação";
    }
    if(isset($parsed_tres['record']['siglaUf'])){
        $uf_nasc = $parsed_tres['record']['siglaUf'];
    }else{
        $uf_nasc = "Sem informação";
    }

    echo('⤬ Município Nascimento: '.$muni_nasc.''.'<br>');
    echo('⤬ Estado Nascimento: '.$uf_nasc.''.'<br>'.'<br>');

    if($parsed['records'][0]['obito'] == False){
        $obito = "Não";
    }
    if($parsed['records'][0]['obito'] == True){
        $obito = "Sim";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://servicos-cloud.saude.gov.br/pni-bff/v1/racacor/'.$parsed['records'][0]['racaCor'].'');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'User-Agent: Mozilla/5.0 (Windows NT '. rand(11, 99) .'.0; Win64; x64) AppleWebKit/'. rand(111, 991) .'.'. rand(11, 99) .' (KHTML, like Gecko) Chrome/'. rand(11, 99) .'.0.0.0 Safari/537.36',
    'Authorization: Bearer '.$bearer_token.'',
    'DNT: 1',
    'Referer: https://si-pni.saude.gov.br/',
    ));
    $re_segundo = curl_exec($ch);
    $parsed_segundo = json_decode($re_segundo, True);
    echo('⤬ Cor: '.$parsed_segundo['record']['descricao'].''.'<br>'.'<br>');
    echo('⤬ Óbito: '.$obito.''.'<br>'.'<br>');
    if(isset($parsed['records'][0]['nomeMae'])){
        $mae = $parsed['records'][0]['nomeMae'];
    }else{
        $mae = 'Sem informação';
    }
    echo('⤬ Mãe: '.$mae.''.'<br>');
    if(isset($parsed['records'][0]['nomePai'])){
        $pai = $parsed['records'][0]['nomePai'];
    }else{
        $pai = 'Sem informação';
    }
    echo('⤬ Pai: '.$pai.''.'<br>'.'<br>');
    echo('📞 Telefones'.'<br>'.'<br>');

    if(isset($parsed['records'][0]['telefone'])){
        foreach ($parsed['records'] as $record) {
            foreach ($record['telefone'] as $telefone) {
                if (isset($telefone['numero'])) {
                    echo $telefone['ddd'].$telefone['numero'].'<br>';
                    $has_numbers = true;
                }
            }
        }
    }else{
        echo('Sem informação'.'<br>');
    }

    echo('<br>'.'🏠 Endereço '.'<br>'.'<br>');
    if(isset($parsed['records'][0]['endereco'])){
        if(isset($parsed['records'][0]['endereco']['logradouro'])){
            $lograd = $parsed['records'][0]['endereco']['logradouro'];
        }else{
            $lograd = "Sem informação";
        }
        echo('⤬ Logradouro: '.$lograd.''.'<br>');
        if(isset($parsed['records'][0]['endereco']['numero'])){
            $num = $parsed['records'][0]['endereco']['numero'];
        }else{
            $num = "Sem informação";
        }
        echo('⤬ Número: '.$num.''.'<br>');
        if(isset($parsed['records'][0]['endereco']['bairro'])){
            $bairro = $parsed['records'][0]['endereco']['bairro'];
        }else{
            $bairro = "Sem informação";
        }
        echo('⤬ Bairro: '.$bairro.''.'<br>');

        if(isset($parsed['records'][0]['endereco']['municipio'])){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://servicos-cloud.saude.gov.br/pni-bff/v1/municipio/'.$parsed['records'][0]['endereco']['municipio'].'');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_ENCODING, "gzip");
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'User-Agent: Mozilla/5.0 (Windows NT '. rand(11, 99) .'.0; Win64; x64) AppleWebKit/'. rand(111, 991) .'.'. rand(11, 99) .' (KHTML, like Gecko) Chrome/'. rand(11, 99) .'.0.0.0 Safari/537.36',
            'Authorization: Bearer '.$bearer_token.'',
            'DNT: 1',
            'Referer: https://si-pni.saude.gov.br/',
            ));
            $re_quatro = curl_exec($ch);
            $parsed_quatro = json_decode($re_quatro, True);

            $municipio = $parsed_quatro['record']['nome'];

        }else{
            $municipio = "Sem informação";
        }

        echo('⤬ Município: '.$municipio.''.'<br>');
        if(isset($parsed['records'][0]['endereco']['siglaUf'])){
            $siglaUf = $parsed['records'][0]['endereco']['siglaUf'];
        }else{
            $siglaUf = "Sem informação";
        }
        echo('⤬ Estado: '.$siglaUf.''.'<br>');
        if(isset($parsed['records'][0]['endereco']['cep'])){
            $cep = $parsed['records'][0]['endereco']['cep'];
        }else{
            $cep = "Sem informação";
        }
        echo('⤬ Cep: '.$cep.''.'<br>'.'<br>');
        echo('💉 Vacinas: '.'<br>'.'<br>');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://servicos-cloud.saude.gov.br/pni-bff/v1/calendario/cpf/'.$cpf.'');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'User-Agent: Mozilla/5.0 (Windows NT '. rand(11, 99) .'.0; Win64; x64) AppleWebKit/'. rand(111, 991) .'.'. rand(11, 99) .' (KHTML, like Gecko) Chrome/'. rand(11, 99) .'.0.0.0 Safari/537.36',
        'Authorization: Bearer '.$bearer_token.'',
        'DNT: 1',
        'Referer: https://si-pni.saude.gov.br/',
        ));
        $re_cinco = curl_exec($ch);
        $parsed_cinco = json_decode($re_cinco, True);

        if($parsed_cinco['record']['imunizacoesCampanha']['imunobiologicos'] == []){
            echo('Sem informação'.'<br>');
        }
        if($parsed_cinco['code'] == 200){
            foreach($parsed_cinco['record']['imunizacoesCampanha']['imunobiologicos'] as $imunobiologico) {
                foreach($imunobiologico['imunizacoes'] as $imunizacao) {
                    echo '---- Tipo: '.$imunizacao['esquemaDose']['tipoDoseDto']['descricao'].'<br>';
                    echo "⤬ Vacina: " . $imunobiologico['sigla'] . "<br>";
                    echo "⤬ Lote: " . $imunizacao['lote'] . "<br>";
                    echo "⤬ Data de Aplicacao: " . $imunizacao['dataAplicacao'] . "<br>";
                    echo "<br>";
                }
            }
        }else{
            echo('Sem informação'.'<br>');
        }
    }

}

?>