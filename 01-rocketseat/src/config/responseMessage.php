<?php

function getErrorMessage($message, $msgToAppend = null) {
    $errorMessages = [
        # Overall
        'feature_not_implemented'       => 'Funcionalidade não implementada ainda.',
        'unknownError'                  => "Um erro desconhecido ocorreu ao $msgToAppend.",
        # JWT
        'wrongJWTSignature'             => 'A chave de segurança não é valida.',
        'missingJWTToken'               => 'Chave de segurança não informada.',
        # Permission
        'userLevelNotImplemented'       => 'Permissão de usuário não implementada.',
        'userLevelNotPermitted'         => 'Permissão de usuário não condizente com solicitação.',
        'userLevelNotFound'             => 'Permissão de usuário não registrada.',
        # Data Base
        'dataBaseNotFound'              => 'Conexão com o banco informada não encontrada, contate o suporte.',
        'onlySingleRelation'            => 'Apenas um relação pode ser criada por vez.',
        'multiTransactionNotStarted'    => 'As chamadas ao banco estão tentando fazer uma transação de multiplos bancos sem iniciar a transação de multiplos bancos.',
        'clientNotFound'                => 'Cliente solicitado para operação não encontrado.',
        'markupNotFound'                => 'Markup solicitado para operação não encontrado.',
        'companyNotFound'               => 'Companhia solicitado para operação não encontrado.',
        'apiNotFound'                   => 'Api Service solicitado para operação não encontrado.',
        'promoCodeNotFound'             => 'Promo Code solicitado para operação não encontrado.',
        'associationAlreadyExist'       => 'Associação já existente.',
        'incorrectAssocParamNum'        => 'Número de associações deve ser igual.',
        'noUserRegistered'              => 'Conjunto de usuario e senha não encontrados.',
        'missingCredentialInformation'  => 'Informações para operar na companhia não encontradas.',
        'uniqueValue'                   => "$msgToAppend já registrado.",
        'dataBaseConnectionFail'        => 'Conexão com o banco de dados não sucedida.',
        # Web services
        'wsNotFound'                    => 'Web Service não encontrado.',
        'wsNotInformed'                 => 'Web Service não informado na requisição.',
        'wsRequestMissingData'          => 'Dado necessário para requisição não encontrado: ',
        'wsError'                       => "Web service erro: $msgToAppend",
        'wsUnrecognizedResponse'        => "Web service, resposta não identificada.",
        'paxNotFoundOnBooking'          => "Passageiro não encontrado no book.",
        "segmentNotBooked"              => "Segmento do voo não corresponde com a reserva.",
        'holdNotPermitted'              => 'Hold não permitido.',
        'ancillaryTypeNotFound'         => 'Tipo de ancillary não encontrado.',
        # Web services (Sabre)
        'wsInternalRequestMissingData'  => "Erro ao montar requisição para companhia, dado necessario não encontrado: $msgToAppend",
        'wsNoErrorTreatmentFound'       => "Tratamento para erro de webservice não encontrado.",
        'noTicketDocument'              => "Não foi encontrado nenhum documento nas busca.",
        # Web services (LATAM)
        'enhancedAirBookNoResponse'     => 'Erro ao precificar, enhancedAirBook sem resposta.',
        'ignoreTransactionError'        => "Erro ao limpar registro de transações da $msgToAppend.",
        # Aerial Overall Errors
        'tripKeyNotValid'               => 'Erro, IATA do aeroporto não encontrada.',
        'notSeatFound'                  => 'Nenhum assento encontrado.',
        'baggageExcess'                 => 'Limite de bagagens por pessoa atingido. Máximo: 5.',
        'productClassNotFound'          => 'Classe de protudo não registrada no sistema.',
        'locNotFoundOrUsetNotIssuer'    => 'Localizador não encontrado ou, usuario atual não é o emissor deste Loc.',
        'differenceBetweenTotalValue'   => 'Diferença entre totalizadores encontrada, favor contatar administradores.',
        'missingPaxs'                   => 'Nenhum passageiro ADT ou CHD informado.',
        'docTypeNotImplemented'         => 'Tipo de documento não implementado.',
        'offerNotFound'                 => 'Oferta de bagagem não encontrado para esse trecho.',
        # Payment
        'paymentTypeNotFound'           => "Método de pagamento $msgToAppend não disponivel.",
        'paymentTypeNotReg'             => "Método de pagamento $msgToAppend não registrado.",
        'paymentNotInformed'            => 'Forma de pagamento não informada.',
        'paymentFieldNotInformed'       => "$msgToAppend do pagamento não informado.",
        'cardFieldNotInformed'          => "$msgToAppend do cartão não informado.",
        'installmentsNotInformed'       => 'Numero de parcelas não informado.',
        #
        'userNotLogged'                 => 'Login necessário.',
        'invalidUserId'                 => 'Identificação de usuário inválido.',
        'invalidCredentialCode'         => 'Código fornecido inválido',
        'credentialCodeNotFound'        => 'Código da credential não encontado.',
        'missingLoc'                    => 'Localizador não informado.',
        'missingBookingLog'             => 'Booking log não informado.',
        'emptyJourneyKey'               => 'Chave da Journey vazia.',
        'missingSessionToken'           => 'Token de sessão não informado',
        'invalidDate'                   => "Data de $msgToAppend informada é invalida.",
        'responseMakerError'            => 'Erro ao converter a resposta. Contate um administrador.',
        'validatorTypeNotFound'         => 'Tipo de validador de dados não encontrado.',
        'dataTypeNotFound'              => 'Tipo de Dado não disponivel para ser Validado/Segurado.',
        'securerDataTypeNotFound'       => 'Tipo de Segurador não encontrado.',
        'missingField'                  => "O campo $msgToAppend não foi informado!.",
        'missingDataToOperation'        => "Parece que um campo interno necessario para a operação esta faltando: $msgToAppend.",
        'incorretJSONStuct'             => "Erro ao validar formato do JSON. Campo necessario: $msgToAppend",
        'incorretPhoneNumberFormat'     => 'Formato do número de telefone incorreto.',
        'invalidBookStatusToPayment'    => "Impossivel efetuar pagamento em reserva com status $msgToAppend.",
        'missingClientInfo'             => 'Informações do cliente não registrada, favor, registre antes de alguma operação',
        'missingConfirmation'           => 'Confirmação da reserva não informado.',
    ];

    return $errorMessages[$message];
}

function getErrorCauseByJsonKey($key) {
    $errorMessages = [
        'from' => 'De',
        'to' => 'Para',
        'dep_date' => 'Data de partida',
        'comp_code' => 'Código IATA da companhia',
        'flight_number' => 'Número do voo',
        'op_suffix' => 'Voo op suffix',
        'trip_segments' => 'Segmentos da viagem',
        'pax_num' => 'Número do passageiro',
        'seats' => 'Assentos',
        'seat-row' => 'Linha do Assento',
        'seat-column' => 'Coluna do Assento',
        'seat-cabin_of_service' => 'Cabine de serviço do Assento',
        'loc' => 'Localizador',
        'paxs_num' => 'Numero de Passageiros',
        'received_by' => 'Recebido Por',
        'trip_info-pax_info' => 'Pax',
        'first_name' => 'Primeiro Nome',
        'middle_name' => 'Nome do meio',
        'last_name' => 'Último Nome',
        'gender' => 'Gênero',
        'address_line1' => 'Endereço',
        'contact_option' => 'Preferências ao Contatar',
        'notification_preference' => 'Preferências de notificação',
        'email' => 'Email',
        'resident_country' => 'País de residencia',
        'pax_resident_country' => 'País de residencia',
        'birth_date' => 'Data de nascimento',
        'received_by' => 'Recebido por',
        'work_phone' => 'Telefone de trabalho'
    ];

    return '"' . $errorMessages[$key] . '"';
}

function getSuccessMessage($message) {
    $successMessages = [
        'dataUpdate' => 'Dado alterado com sucesso.',
        'dataInsert' => 'Dado inserido com sucesso.',
        'dataDelete' => 'Dado removido com sucesso.',
    ];

    return $successMessages[$message];
}

function getSuccessApisMessage($message) {
    $successMessages = [
        'sessionCleared' => 'Sessão(ões) limpa(as) com sucesso.'
    ];

    return $successMessages[$message];
}