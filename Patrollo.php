<?php

require_once __DIR__ . '/vendor/autoload.php';
use deemru\WavesKit;

if( file_exists( __DIR__ . '/config.php' ) )
    require_once __DIR__ . '/config.php';
else
    require_once __DIR__ . '/config.sample.php';

$patrollo->log( 'i', 'Patrollo: ' . $patrollo->getAddress() );
$game->log( 'i', 'Game: ' . $game->getAddress() );

$nodes = [];
$n = count( $nodes_urls );
for( $i = 0; $i < $n; $i++ )
{
    $nodes[$i] = new WavesKit();
    $nodes[$i]->setNodeAddress( $nodes_urls[$i] );
}

for( $height = 0;; )
{
    if( isset( $ts ) && microtime( true ) - $ts < 5 )
    {
        if( isset( $blocksFTW ) && $blocksFTW > 10 )
            sleep( 37 );
        else
            sleep( 3 );
    }

    $ts = microtime( true );
    $last_height = $height;
    foreach( $nodes as $node )
    {
        $node_height = $node->height();
        if( $node_height > $height )
        {
            $height = $node_height;
            $game->setNodeAddress( $node->getNodeAddress() );
        }
    }

    if( $last_height === $height && $blocksFTW > $threshold )
        continue;

    $heightToGetMoney = $game->getData( 'heightToGetMoney' );
    $lastPayment = $game->getData( 'lastPayment' );
    if( $heightToGetMoney === false || $lastPayment === false )
        continue;

    $blocksFTW = $heightToGetMoney - $height;

    if( !isset( $currentPayment ) || $currentPayment !== $lastPayment )
    {
        $tx = $game->getTransactionById( $lastPayment );
        if( $tx === false )
            continue;

        if( $tx['sender'] === $patrollo->getAddress() )
        {
            $id = $lastPayment;
            $game->log( 's', "Patrollo is lastPayment ($id)" );
        }
        else
        {
            unset( $id );
            $sender = $tx['sender'];
            $game->log( 'i', "lastPayment changed ($sender)" );
            sendPatrolloReport( 'lastPayment changed', "https://wavesexplorer.com/address/$sender" );
        }

        $currentPayment = $lastPayment;
    }

    $game->log( 'i', 'blocks left = ' . $blocksFTW . ( $id === $lastPayment ? ' (for the win!)' : ' (to lose)' ) );

    if( $blocksFTW <= 0 )
    {
        $game->log( 's', 'game ended' );
        $lastPayment = $game->getData( 'lastPayment' );
        $tx = $game->getTransactionById( $lastPayment );
        $winner = $tx['sender'];
        $game->log( 's', "Game ends: winner is $winner" );
        sendPatrolloReport( 'Game ends', "winner is $winner" );
        exit;
    }

    if( $blocksFTW >= 1 && !isset( $id ) )
    {
        $file = $patrollo->getAddress() . '.txt';
        if( file_exists( $file ) )
        {
            $ids = $patrollo->json_decode( file_get_contents( $file ) );
            if( count( $ids ) )
            {
                $id = $ids[0];
                if( false !== $game->getData( $id ) )
                {
                    unset( $id );
                    $ids = array_slice( $ids, 1 );
                    file_put_contents( $file, json_encode( $ids ) );
                }
            }
        }

        if( !isset( $id ) )
        {
            $game->log( 'i', "Making payment" );
            $waves = $patrollo->balance();
            $tx = $game->txBroadcast( $patrollo->txSign( $patrollo->txTransfer( $game->getAddress(), 110000000, 0, [ 'attachment' => $patrollo->base58Encode( 'Patrollo' ) ] ) ) );
            if( $tx === false )
            {
                $game->log( 'e', "Making payment failed" );
                sendPatrolloReport( 'Making payment failed', 'https://wavesexplorer.com/address/' . $patrollo->getAddress() );
                $waves = $game->balance( $patrollo->getAddress() );
                $waves = $waves[0]['balance'];
                if( $waves < 110100000 )
                {
                    $game->log( 'e', "No waves left: $waves" );
                    sendPatrolloReport( 'No waves left', "$waves: https://wavesexplorer.com/address/" . $patrollo->getAddress() );
                    $game->log( 'i', "relax (for 1 min)..." );
                    sleep( 60 );
                }
                continue;
            }

            $tx = $game->ensure( $tx );
            if( $tx === false )
                continue;

            $game->log( 's', "Payment done" );
            if( !isset( $ids ) )
                $ids = [];

            $id = $tx['id'];
            $game->log( 's', "1.1 Waves transfered: new id = $id" );
            sendPatrolloReport( '1.1 Waves transfered', "https://wavesexplorer.com/tx/$id" );

            $ids[] = $id;
            file_put_contents( $file, json_encode( $ids ) );
        }
    }

    if( $blocksFTW <= $threshold && $lastPayment !== $id )
    {
        $game->log( 'i', "Making bet" );
        $tx = $game->txBroadcast( $patrollo->txSign( $game->txData( [ 'heightToGetMoney' => $height + 60, 'lastPayment' => $id, $id => 'Patrollo' ], [ 'fee' => 10000000 ] ) ) );
        if( $tx === false )
        {
            $game->log( 'e', "Making bet failed" );
            sendPatrolloReport( 'Making bet failed', 'https://wavesexplorer.com/address/' . $patrollo->getAddress() );
            continue;
        }

        $tx = $game->ensure( $tx );
        if( $tx === false )
            continue;

        $game->log( 's', "Bet done" );
        sendPatrolloReport( 'Bet done', $id );

        unset( $id );
        $ids = array_slice( $ids, 1 );
        file_put_contents( $file, json_encode( $ids ) );
        $game->log( 'i', "relax (for 1 min)..." );
        sleep( 60 );
    }
}
