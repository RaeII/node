<?php
namespace Controller;

use \Service\BookingLogService;

class BookingLogController extends Controller {

    public function __construct() {
        $this->service = new BookingLogService();
    }

    public function create(Array $booking, Int $clientId) {
        $blJourneyService = new \Service\BookingLogJourneysService();
        $blSegmentsService = new \Service\BookingLogSegmentsService();
        $blJourneyFaresService = new \Service\BookingLogJourneyFaresService();
        $blJourneyFareTaxsService = new \Service\BookingLogJourneyFareTaxsService();
        $blPaxsService = new \Service\BookingLogPaxsService();
        $blPaxsTaxsService = new \Service\BookingLogPaxsTaxsService();
        $clientService = new \Service\ClientOutService();

        $client = $clientService->fetch($clientId);
        
        $blId = $this->service->create($booking, $client);

        $this->service->startTransaction('mysql');

        $booking['booking_log_id'] = $blId;

        foreach ($booking['journeys'] as $journey) {
            $journeyId = $blJourneyService->create($journey['segments'], $blId);
            $fare =  $journey['fares'][0];

            $blSegmentsService->create($journey['segments'], $journeyId);

            foreach ($fare['paxs_fare'] as $paxFare) {
                $journeyFaresId = 0;

                $journeyFaresId = $blJourneyFaresService->create($paxFare, $fare['product_class'], $fare['service_class'], $journeyId);
                foreach ($paxFare['taxes'] as $taxe) {
                    $blJourneyFareTaxsService->create($taxe, $journeyFaresId);
                }
            }
        }

        foreach ($booking['paxs'] as $pax) {
            $paxId = $blPaxsService->create($pax, $blId);
            $blPaxsTaxsService->create($pax['fees'], $paxId);
        }
        $this->service->commit('mysql');
        // $blSegmentsService = $blSegmentsService->create($booking);
    }

    public function fetchByLoc(String $loc, Int $userId = null) {
        return $this->service->fetchByLoc($loc, $userId);
    }
}
?>