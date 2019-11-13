<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class   Average30days_model extends CI_model {

    public function oneMonthsWorth($receive_array) {
        $this->load->model('sqlqu');
        $average_ms = array();
        $request_array = array(
            'request_type'          => 'single_ip_one_month',
            'host'                    => $receive_array['host'],
        );
        $one_months_worth = $this->sqlqu->getHistoricNovascore($request_array);

        foreach($one_months_worth->result() as $row) {
            if($row->ms !== '0') {
                $average_ms[] = $row->ms;
            }
        }
        return $average_ms;
    }

    public function returnAverage($array) {
        if(count($array)) {
            $array = array_filter($array);
            $average = array_sum($array) / count($array);
            return round($average,0);
        } else {
            return false;
        }
    }

    public function returnMedian($array) {
          $iCount = count($array);
          if ($iCount == 0) {
            throw new DomainException('Median of an empty array is undefined');
          }
          $middle_index = floor($iCount / 2);
          sort($array, SORT_NUMERIC);
          $median = $array[$middle_index];
          if ($iCount % 2 == 0) {
            $median = ($median + $array[$middle_index - 1]) / 2;
          }
          return $median;
    }

    public function returnMostOccured($array)
    {
          $counted = array_count_values($array);
          arsort($counted);
          return(key($counted));
    }

    public function updateAverage($array)
    {
        $update_array = array(
            'average_longterm_ms' => $array['average_longterm_ms'],
        );
        $this->db->where('ip', $array['host']);
        $this->db->update('ping_ip_table', $update_array);
    }

    /**
     * special kind of algorithm this one, as it will be used as one of the orber_by metrics for the nodes on the main
     * page.
     * 'last_ms'                    =>
     * 'average_longterm_ms'        =>
     */
    public function ltaCurrentMsDifference($array) {
        $percent_n_ms_diff = $this->average30days_model->getPercentAndMsForDiff();
        $difference_algo = 0;
        $difference_percent = 0;

        $difference_ms = $array['average_longterm_ms'] - $array['last_ms'];
        if($array['last_ms'] != 0) $difference_percent = round((1 - $array['average_longterm_ms']/$array['last_ms'])*100,0);

        if($difference_ms < 0) { //slower than usual response times
            if($difference_ms <="-".$percent_n_ms_diff['ms_diff'] && $difference_percent > $percent_n_ms_diff['percent_diff']) {
                $difference_algo = "-".$difference_percent-100; //need the minus so the nodes, both slow and fast, are stored at the top of the icmpview
                //we minus 100 to amke it easier to select the slow ones from the fast ones in our views
            }
        } else if($difference_ms >= $percent_n_ms_diff['ms_diff'] && $difference_percent < "-".$percent_n_ms_diff['percent_diff']) { //faster than usual response times
                $difference_algo = $difference_percent;
        }
        return $difference_algo;
    }

    public function getPercentAndMsForDiff() {
        $retuenArray = array();
        $this->db->where('id', 1);
        $otherTable_percent = $this->db->get('other');
        $retuenArray['percent_diff'] = $otherTable_percent->row('value');

        $this->db->where('id', 2);
        $otherTable_ms = $this->db->get('other');
        $retuenArray['ms_diff'] = $otherTable_ms->row('value');
        return $retuenArray;
    }
} 
