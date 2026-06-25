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

/**
 * Deriva, SOMENTE LEITURA, o status normalizado de um pedido.
 * Espelha a precedência de status já usada em backend/api/tracking.php.
 *
 * @param array      $pedido   Linha da tabela `pedidos`
 * @param array|null $tracking Linha da tabela `order_tracking` (ou null se não existir)
 * @return array {
 *   status_pagamento: string,
 *   status_envio: int|null,
 *   status_envio_label: string|null,
 *   codigo_rastreio: string|null,
 *   tracking_url: string|null,
 *   carrier: string|null,
 *   prazo: int|null,
 *   exibir_rastreio: bool
 * }
 */
function derivarStatusPedido(array $pedido, ?array $tracking): array
{
    $statusPagamento = $pedido['status'] ?? 'pendente';
    $exibirRastreio  = statusPermiteRastreamento($statusPagamento);

    $statusEnvio      = null;
    $statusEnvioLabel = null;
    $carrier          = null;
    $prazo            = null;

    if ($tracking !== null) {
        $labels           = ['Em Preparação', 'Embalado', 'Enviado', 'Código de Rastreio'];
        $statusEnvio      = (int) $tracking['status'];
        $statusEnvioLabel = $labels[$statusEnvio] ?? 'Desconhecido';
        $carrier          = $tracking['carrier']           ?? null;
        $prazo            = isset($tracking['shipping_deadline']) ? (int) $tracking['shipping_deadline'] : null;
    }

    // Preferir tracking_code do order_tracking; fallback para coluna legada em pedidos
    $codigoRastreio = $tracking['tracking_code'] ?? ($pedido['codigo_rastreio'] ?? null);
    if ($codigoRastreio === '') $codigoRastreio = null;

    $trackingUrl = null;
    if ($codigoRastreio && preg_match('/^[A-Z]{2}[0-9]{9}BR$/', $codigoRastreio)) {
        $trackingUrl = 'https://rastreamento.correios.com.br/app/index.php?objetos='
                     . urlencode($codigoRastreio);
    }

    return [
        'status_pagamento'  => $statusPagamento,
        'status_envio'      => $statusEnvio,
        'status_envio_label'=> $statusEnvioLabel,
        'codigo_rastreio'   => $exibirRastreio ? $codigoRastreio : null,
        'tracking_url'      => $exibirRastreio ? $trackingUrl    : null,
        'carrier'           => $carrier,
        'prazo'             => $prazo,
        'exibir_rastreio'   => $exibirRastreio,
    ];
}
