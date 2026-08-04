import json
from http.server import BaseHTTPRequestHandler, HTTPServer

PORT = 5001

class SMSWebhookHandler(BaseHTTPRequestHandler):
    def do_POST(self):
        # 1. Read the length of the incoming data
        content_length = int(self.headers['Content-Length'])
        post_data = self.rfile.read(content_length)
        
        try:
            # 2. Parse the incoming JSON payload from the phone
            payload = json.loads(post_data.decode('utf-8'))
            print("\n=== New SMS Received ===")
            print(f"From: {payload.get('from', 'Unknown')}")
            print(f"Message: {payload.get('message', '')}")
            
            # 3. Formulate the automatic reply payload
            # The app expects a JSON response containing an array of messages to send back.
            reply_data = {
                "to": payload.get('from'),
                "message": "Your secure transaction voucher PIN code is: 8492"
            }
            
            # Wrap inside the expected response array structure
            response_payload = [reply_data]
            
            # 4. Send a successful HTTP 200 response back to the phone
            self.send_response(200)
            self.send_header('Content-Type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps(response_payload).encode('utf-8'))
            print("🚀 Reply payload successfully returned to the gateway app.")
            
        except Exception as e:
            print(f"❌ Error processing payload: {e}")
            self.send_response(500)
            self.end_headers()

def run_server():
    server_address = ('', PORT)
    httpd = HTTPServer(server_address, SMSWebhookHandler)
    print(f"📡 Webhook listener active! Point your gateway app to http://YOUR_COMPUTER_IP:{PORT}")
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\nStopping listener server...")
        httpd.server_close()

if __name__ == '__main__':
    run_server()
