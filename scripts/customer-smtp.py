"""Local SMTP inbox for integration tests only; never forwards mail externally."""
from pathlib import Path
import socketserver
import uuid

MAILBOX = Path(__file__).resolve().parents[1] / '.local' / 'mailbox'
MAILBOX.mkdir(parents=True, exist_ok=True)

class SMTP(socketserver.StreamRequestHandler):
    def reply(self, message):
        self.wfile.write((message + '\r\n').encode('ascii'))
        self.wfile.flush()

    def handle(self):
        self.reply('220 localhost Customer test SMTP')
        while True:
            line = self.rfile.readline(65537)
            if not line: return
            command = line.decode('ascii', errors='replace').strip().upper()
            if command.startswith(('EHLO', 'HELO')): self.reply('250 localhost')
            elif command.startswith(('MAIL FROM:', 'RCPT TO:', 'RSET', 'NOOP')): self.reply('250 OK')
            elif command == 'DATA':
                self.reply('354 End with dot')
                content = bytearray()
                while True:
                    chunk = self.rfile.readline(65537)
                    if not chunk: return
                    if chunk in (b'.\r\n', b'.\n'): break
                    content.extend(chunk[1:] if chunk.startswith(b'..') else chunk)
                    if len(content) > 1024 * 1024:
                        self.reply('552 Too large'); return
                (MAILBOX / (uuid.uuid4().hex + '.eml')).write_bytes(content)
                self.reply('250 Stored in local test inbox')
            elif command == 'QUIT':
                self.reply('221 Bye'); return
            else: self.reply('502 Command not implemented')

class Server(socketserver.ThreadingTCPServer):
    allow_reuse_address = True

if __name__ == '__main__':
    with Server(('127.0.0.1', 1025), SMTP) as server:
        server.serve_forever()
