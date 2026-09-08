<?php

$notifications = \Illuminate\Notifications\DatabaseNotification::all();
$count = 0;
foreach ($notifications as $notification) {
    if (isset($notification->data['actions'])) {
        $data = $notification->data;
        $updated = false;
        foreach ($data['actions'] as &$action) {
            if (isset($action['isOutlined'])) {
                $action['isOutlined'] = false;
            }
            if (isset($action['isButton'])) {
                $action['isButton'] = false;
            }
            if (isset($action['button'])) {
                $action['button'] = false;
            }
            $action['isLink'] = true;
            $action['size'] = 'sm';

            if ($action['name'] === 'markAsRead' || $action['label'] === 'Marcar leída' || $action['label'] === 'Marcar como leída') {
                $action['label'] = 'Leída';
                $action['color'] = 'success';
                $action['icon'] = 'heroicon-m-check';
            } elseif ($action['name'] === 'postpone' || $action['label'] === 'Posponer' || $action['label'] === 'Posponer 24h') {
                $action['label'] = 'Posponer';
                $action['color'] = 'warning';
                $action['icon'] = 'heroicon-m-clock';
            } elseif ($action['name'] === 'goToAction' || $action['label'] === 'Revisar' || $action['label'] === 'Revisar entrada') {
                $action['label'] = 'Revisar';
                $action['color'] = 'primary';
                $action['icon'] = 'heroicon-m-eye';
            }
            $updated = true;
        }
        if ($updated) {
            $notification->data = $data;
            $notification->save();
            $count++;
        }
    }
}
echo "Updated $count notifications.\n";

