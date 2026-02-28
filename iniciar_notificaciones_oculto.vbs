Set WshShell = CreateObject("WScript.Shell")
WshShell.Run chr(34) & "c:\xampp\htdocs\project\iniciar_notificaciones.bat" & Chr(34), 0
Set WshShell = Nothing
