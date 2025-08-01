from diagrams import Diagram, Cluster
from diagrams.programming.language import Python
from diagrams.programming.framework import Flask
from diagrams.generic.storage import Storage
from diagrams.generic.device import Mobile

def create_ipo_diagram():
    with Diagram("Chatbot IPO Model", filename="docs/diagrams/chatbot_ipo", show=False, direction="LR"):
        # Input nodes
        with Cluster("Inputs"):
            inputs = [
                Mobile("Webpage URLs"),
                Storage("Document Files"),
                Python("User Messages")
            ]
        
        # Processing nodes
        with Cluster("Processing"):
            webpage_proc = Flask("Webpage Processing")
            file_proc = Flask("File Processing")
            embedding = Flask("Embedding Generation")
            llm = Flask("LLM Processing")
        
        # Output nodes
        with Cluster("Outputs"):
            chat_resp = Python("Chat Responses")
            stored_data = Storage("Stored Data")
            status = Mobile("Status")
        
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
