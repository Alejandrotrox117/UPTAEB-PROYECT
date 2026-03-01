Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
strPath = fso.GetParentFolderName(WScript.ScriptFullName)

WshShell.Run "cmd.exe /c cd /d """ & strPath & "\redis"" && redis-server.exe redis.windows.conf", 0, false
WScript.Sleep 2000
WshShell.Run "cmd.exe /c cd /d """ & strPath & """ && php server.php", 0, false

MsgBox "Servicios de Notificaciones (Redis y WebSocket) iniciados de forma oculta." & vbCrLf & vbCrLf & "Para detenerlos, deberas abrir el Administrador de Tareas y finalizar los procesos 'redis-server.exe' y 'php.exe'.", vbInformation, "Notificaciones UPTAEB"
