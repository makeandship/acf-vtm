<?php 

class MedicinesApi { 
	const MEDICINES_SERVICE_SCHEME = 'http'; 
	const MEDICINES_SERVICE_HOST = 'api.medicines.makeandship.com';
	     
	public function get_endpoint($path, $query) {
		$query_string = null;
		if ($query) {
			$query_string = http_build_query($query);
		}

		$uri = self::MEDICINES_SERVICE_SCHEME.'://'.self::MEDICINES_SERVICE_HOST.$path;
		if ($query_string) {
			$uri = $uri.'?'.$query_string;
		}

		return $uri;
	}

    public function ampps($query) {
		// full response for AMPP 
		$query['scheme'] = 'core';
		
    	$uri = $this->get_endpoint('/virtual_therapeutic_moieties.js', $query);

		$request_args = array(
			'timeout' => 30	
		);
        $request = wp_remote_get( $uri, $request_args );
		$response = json_decode( $request['body'], true );
		
		$vtms = $response['data'];
		
		$results = array();
		$index = 0;
		
		foreach ($vtms as $vtm) {
			$vtm_name = $vtm['name'];
			$ampps = array(
				"text"=> $vtm_name,
				"children" => array()
			);
			foreach($vtm['virtual_medicinal_products'] as $vmp) {
				foreach($vmp['virtual_medicinal_product_packs'] as $vmpp) {
					foreach($vmpp['actual_medicinal_product_packs'] as $ampp) {
						$ampp_id = strval($ampp['id']);
						$ampp_name = $ampp['name'];
						
						$title = $ampp_name;
						
						$entry = array("id" => $ampp_id, "text" => $title);
						array_push($ampps["children"], $entry);
					}
				}
			}
			
			array_push($results, $ampps);
		}
		
		return $results;
    } 
	
	public function ampp($id) {
		$query = array();
		$query['scheme'] = 'core';
		
    	$uri = $this->get_endpoint('/actual_medicinal_product_packs/'.$id.'.js', $query);

		$request_args = array(
			'timeout' => 30	
		);
        $request = wp_remote_get( $uri, $request_args );
		$response = json_decode( $request['body'], true );
		
		return $response['data'];
	}
} 
