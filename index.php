<?php

$url = $_SERVER[ 'REQUEST_URI' ];
$metodo = $_SERVER[ 'REQUEST_METHOD' ];

// curl -X GET http://localhost/api/cervejas
//
$dir = dirname( $_SERVER[ 'PHP_SELF' ] ); // http://localhost/api
$caminho = str_replace( $dir, '', $url ); // /cervejas

try {
    $pdo = new PDO( 'mysql:dbname=acme;host=localhost;charset=UTF8', 'root', '',
        [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION ] );
} catch ( Exception $e ) {
    die( $e->getMessage() );
}

if ( $caminho == '/cervejas' ) {
    if ( $metodo == 'GET' ) {
        header( 'Content-Type: application/json' );
        $cervejas = consultarCervejas( $pdo );
        echo json_encode( $cervejas );
        die();
    } else if ( $metodo == 'POST' ) {
        $conteudo = file_get_contents( 'php://input' ); // Corpo
        $array = (array) json_decode( $conteudo );
        cadastrarCerveja( $pdo, $array[ 'nome' ] );
        http_response_code( 201 ); // Created
        die( 'Salvo com sucesso.' );
    }

// "/cervejas/1" -> [ '', 'cervejas', '1' ]
} else if ( mb_strpos( $caminho, '/cervejas/' ) === 0 ) { // Começa com "/cervejas/"
    list( , , $id ) = explode( '/', $caminho );
    if ( ! is_numeric( $id ) ) {
        http_response_code( 400 ); // Bad Content
        die( 'Por favor informe um id numérico.' );
    }
    if ( $metodo == 'DELETE' ) {
        try {
            $ok = removerCervejaPeloId( $pdo, $id );
            if ( ! $ok ) {
                http_response_code( 404 );
                die( 'Não encontrada.' );
            }
        } catch ( Exception $e ) {
            http_response_code( 500 ); // Server Error
            die( 'Erro ao remover a cerveja');
        }
        http_response_code( 204 ); // No Content
        die();
    } else if ( $metodo == 'GET' ) {
        $cerveja = cervejaComId( $pdo, $id );
        if ( ! isset( $cerveja ) ) {
            http_response_code( 404 );
            die( 'Não encontrada.' );
        }
        header( 'Content-Type: application/json' );
        echo json_encode( $cerveja );
        die();
    } else if ( $metodo == 'PUT' ) {
        $conteudo = file_get_contents( 'php://input' ); // Corpo
        $array = (array) json_decode( $conteudo );
        $ok = alterarCerveja( $pdo, $array[ 'nome' ], $id );
        if ( ! $ok ) {
            http_response_code( 404 );
            die( 'Não encontrada.' );
        }
        http_response_code( 201 ); // Created
        die( 'Salvo com sucesso.' );
    }
} else {
    http_response_code( 404 );
    die( 'Nao encontrado' );
}




function consultarCervejas( $pdo ) {
    $ps = $pdo->prepare( 'SELECT * from cerveja' );
    $ps->execute();
    $cervejas = $ps->fetchAll( PDO::FETCH_ASSOC );
    // [ [ 'id' => 1, 'nome' => 'Skol' ] ];
    return $cervejas;
}

function cadastrarCerveja( $pdo, $nome ) {
    $ps = $pdo->prepare( 'INSERT INTO cerveja ( nome ) VALUES ( ? )' );
    $ps->execute( [ $nome ] );
}


function removerCervejaPeloId( $pdo, $id ) {
    $ps = $pdo->prepare( 'DELETE FROM cerveja WHERE id = ?' );
    $ps->execute( [ $id ] );
    return $ps->rowCount() > 0;
}

function cervejaComId( $pdo, $id ) {
    $ps = $pdo->prepare( 'SELECT * from cerveja WHERE id = ?' );
    $ps->execute( [ $id ]);
    $cerveja = $ps->fetch( PDO::FETCH_ASSOC );
    if ( $cerveja === false ) {
        return null;
    }
    return $cerveja;
}

function alterarCerveja( $pdo, $nome, $id ) {
    $ps = $pdo->prepare( 'UPDATE cerveja SET nome = :nome WHERE id = :id' );
    $ps->execute( [ 'nome' => $nome, 'id' => $id ] );
    return $ps->rowCount() > 0;
}

?>
