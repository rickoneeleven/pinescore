<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Events extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('events_model');
    }

    public function index()
    {
        $owner_id = $this->session->userdata('user_id');
        if (!$owner_id) {
            redirect('auth/user/login');
            return;
        }

        $context = $this->resolve_group($owner_id, $this->input->get('group'), true);
        $group_id = $context['id'];
        $group_name = $context['name'];

        $data_meta = [
            'title' => 'Events',
            'description' => 'Recent node events with timeline and live bar.',
            'keywords' => 'events,icmp,pings',
            'group_id' => $group_id,
            'refresh_content' => '',
        ];

        $data = [
            'group_id' => $group_id,
            'group_name' => $group_name,
            'default_window' => '24h',
        ];

        $this->load->view('header_view', $data_meta);
        $this->load->view('navTop_view', $data_meta);
        $this->load->view('reports/events_view', $data);
        $this->load->view('footer_view');
    }

    public function bar()
    {
        $owner_id = $this->session->userdata('user_id');
        if (!$owner_id) {
            $this->output_json(['error' => 'Authentication required'], 401);
            return;
        }

        $context = $this->resolve_group($owner_id, $this->input->get('group'), false);
        if ($context['status']) {
            $this->output_json(['error' => $context['message']], $context['status']);
            return;
        }

        $items = $this->events_model->fetch_recent_events($owner_id, $context['id']);
        $this->output_json($items);
    }

    public function json()
    {
        $owner_id = $this->session->userdata('user_id');
        if (!$owner_id) {
            $this->output_json(['error' => 'Authentication required'], 401);
            return;
        }

        $context = $this->resolve_group($owner_id, $this->input->get('group'), false);
        if ($context['status']) {
            $this->output_json(['error' => $context['message']], $context['status']);
            return;
        }

        $window = $this->input->get('window');
        $cursor = $this->input->get('cursor');
        $limit = $this->input->get('limit');
        $search = $this->input->get('q');

        $result = $this->events_model->fetch_events_window(
            $owner_id,
            $context['id'],
            $window,
            $cursor,
            $limit,
            $search
        );

        $response = ['items' => $result['items']];
        if (!empty($result['next_cursor'])) {
            $response['next_cursor'] = $result['next_cursor'];
        }

        $this->output_json($response);
    }

    private function resolve_group($owner_id, $group_param, $strict)
    {
        if ($group_param === null || $group_param === '' || $group_param === false) {
            return ['id' => null, 'name' => null, 'status' => 0, 'message' => null];
        }

        if (!ctype_digit((string) $group_param)) {
            $message = 'Invalid group parameter';
            if ($strict) {
                show_error($message, 400);
            }

            return ['id' => null, 'name' => null, 'status' => 400, 'message' => $message];
        }

        $group_id = (int) $group_param;
        if ($group_id < 1) {
            $message = 'Invalid group parameter';
            if ($strict) {
                show_error($message, 400);
            }

            return ['id' => null, 'name' => null, 'status' => 400, 'message' => $message];
        }

        $this->load->model('group');
        $group = $this->group->readSpecificGroup([
            'user_id' => $owner_id,
            'group_id' => $group_id,
        ]);

        if ($group->num_rows() < 1) {
            $message = 'Group not found';
            if ($strict) {
                show_error($message, 404);
            }

            return ['id' => null, 'name' => null, 'status' => 404, 'message' => $message];
        }

        return [
            'id' => $group_id,
            'name' => $group->row('name'),
            'status' => 0,
            'message' => null,
        ];
    }

    private function output_json($payload, $status = 200)
    {
        if (function_exists('http_response_code')) {
            http_response_code($status);
        } else {
            header($this->status_header_line($status));
        }
        header('Content-Type: application/json');
        echo json_encode($payload);
    }

    private function status_header_line($status)
    {
        $texts = [
            200 => 'OK',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
        ];

        $text = isset($texts[$status]) ? $texts[$status] : 'OK';

        return 'HTTP/1.1 ' . $status . ' ' . $text;
    }
}
