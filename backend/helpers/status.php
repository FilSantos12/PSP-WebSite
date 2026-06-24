<?php
/**
 * Helpers centralizados de status de pedido.
 * Toda derivação de status MP → interno passa por aqui.
 */

/**
 * Mapeia o status retornado pelo Mercado Pago para o status interno do pedido.
 */
function mpStatusParaInterno(string $mpStatus): string {
    $map = [
        'approved'     => 'aprovado',
        'pending'      => 'pendente',
        'in_process'   => 'em_analise',
        'rejected'     => 'recusado',
        'cancelled'    => 'cancelado',
        'refunded'     => 'reembolsado',
        'charged_back' => 'contestado',
    ];
    return $map[$mpStatus] ?? 'pendente';
}

/**
 * Retorna true apenas para pedidos cujo pagamento está confirmado,
 * ou seja, onde faz sentido exibir o rastreamento físico ao admin/cliente.
 * Pedidos recusados, cancelados etc. não devem mostrar "Em Preparação".
 */
function statusPermiteRastreamento(string $pedidoStatus): bool {
    return in_array($pedidoStatus, ['aprovado', 'em_processamento'], true);
}
