from diagrams import Diagram, Cluster
from diagrams.programming.language import Go, Php, Python
from diagrams.programming.framework import Laravel
from diagrams.generic.network import Router
from diagrams.generic.storage import Storage
from diagrams.generic.device import Mobile
from diagrams.onprem.client import Client, Users

def create_ipo_diagram():
    with Diagram("Chatbot IPO Model", filename="docs/diagrams/chatbot_ipo", show=False, direction="TB"):
        # Input nodes
        with Cluster("Inputs"):
            inputs = [
                Router("Webpage URLs"),
                Storage("Document Files"),
                Users("User Messages")
            ]
        
        # Processing nodes
        with Cluster("Processing"):
            webpage_proc = Php("Webpage Processing")
            file_proc = Php("File Processing")
            embedding = Go("Embedding Generation")
            llm = Python("LLM Processing")
        
        # Output nodes
        with Cluster("Outputs"):
            chat_resp = Laravel("Chat Responses")
            stored_data = Storage("Stored Data")
            status = Client("Status")
        
        # Connect nodes
        inputs[0] >> webpage_proc >> embedding
        inputs[1] >> file_proc >> embedding
        embedding >> stored_data
        inputs[2] >> llm
        stored_data >> llm >> chat_resp
        webpage_proc >> status
        file_proc >> status

if __name__ == "__main__":
    create_ipo_diagram()
