<?php
function statusBadge(string $s): string {
    $map = ['Submitted' => 'submitted', 'In Progress' => 'inprogress', 'Resolved' => 'resolved'];
    $cls = $map[$s] ?? 'submitted';
    return "<span class='badge badge-{$cls}'>{$s}</span>";
}

function priorityBadge(string $p): string {
    $map = ['High' => 'high', 'Medium' => 'medium', 'Low' => 'low'];
    $cls = $map[$p] ?? 'low';
    return "<span class='badge badge-{$cls}'>{$p}</span>";
}

function resolutionTime(string $submitted, string $resolved): string {
    if (empty($resolved)) return '—';
    $diff = strtotime($resolved) - strtotime($submitted);
    if ($diff < 60)     return $diff . 's';
    if ($diff < 3600)   return round($diff/60) . ' min';
    if ($diff < 86400)  return round($diff/3600, 1) . ' hrs';
    return round($diff/86400, 1) . ' days';
}

function loadComplaints(): array {
    $path = __DIR__ . '/../data/complaints.json';
    return json_decode(file_get_contents($path), true) ?? [];
}

function saveComplaints(array $data): void {
    $path = __DIR__ . '/../data/complaints.json';
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
}

function nextComplaintId(array $complaints): string {
    $max = 0;
    foreach ($complaints as $c) {
        $n = (int) ltrim($c['id'], 'CMP');
        if ($n > $max) $max = $n;
    }
    return 'CMP' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

// Returns notifications for a user based on role/username
function getNotifications(array $complaints, string $username, string $role): array {
    $notes = [];
    foreach ($complaints as $c) {
        if ($role === 'student' && $c['student_username'] === $username) {
            if ($c['status'] === 'In Progress' && $c['assigned_to'])
                $notes[] = ['msg' => "#{$c['id']} assigned to staff — now In Progress.", 'time' => $c['submitted_at']];
            if ($c['status'] === 'Resolved')
                $notes[] = ['msg' => "#{$c['id']} has been resolved! ✅", 'time' => $c['resolved_at']];
        }
        if ($role === 'staff' && $c['assigned_to'] === $username && $c['status'] === 'In Progress')
            $notes[] = ['msg' => "#{$c['id']} assigned to you — {$c['category']}.", 'time' => $c['submitted_at']];
        if ($role === 'admin' && $c['status'] === 'Submitted')
            $notes[] = ['msg' => "#{$c['id']} submitted and needs assignment.", 'time' => $c['submitted_at']];
    }
    return array_slice($notes, 0, 8);
}
