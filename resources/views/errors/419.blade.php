@extends('errors.layout')

@section('code', 'Sitzung abgelaufen')
@section('title', 'Ihre Sitzung ist abgelaufen')
@section('message', 'Aus Sicherheitsgründen wurden Sie abgemeldet. Bitte melden Sie sich erneut an und versuchen Sie es noch einmal.')
@section('action_url', route('login'))
@section('action_label', 'Zur Anmeldung')
