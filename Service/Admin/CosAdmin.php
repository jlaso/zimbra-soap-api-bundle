<?php
/**
 * Admin class to query the ZCS api for COS related requests.
 *
 * @author Chris Ramakers <chris@nucleus.be>
 * @license http://www.gnu.org/licenses/gpl.txt
 */
namespace Jlaso\ZimbraSoapApiBundle\Service\Admin;

use Jlaso\ZimbraSoapApiBundle\Service\ZCS\Admin;
use Jlaso\ZimbraSoapApiBundle\Entity\Cos;
use Jlaso\ZimbraSoapApiBundle\Service\ZCS\SoapClient;

class CosAdmin extends Admin
{
    /**
     * Fetches all Classes of Service from the soap webservice and returns them as an array
     * containing Cos objects
     *
     * @return Cos[]
     */
    public function getCosList()
    {
        $cosList = $this->soapClient->request('GetAllCosRequest')->children()->GetAllCosResponse->cos;

        $results = array();
        foreach ($cosList as $cos) {
            $results[] = Cos::createFromXml($cos);
        }
        return $results;
    }

    /**
     * Fetches a single class of service from the webservice and returns it
     * as a Cos object
     *
     * @param string $cos
     *
     * @return Cos
     */
    public function getCos($cos)
    {
        $params = array(
            'cos' => array(
                '_'  => $cos,
                'by' => 'id',
            )
        );

        $response = $this->soapClient->request('GetCosRequest', $params);
        $coslist = $response->children()->GetCosResponse->children()->cos;

        return Cos::createFromXml($coslist);
    }

}
