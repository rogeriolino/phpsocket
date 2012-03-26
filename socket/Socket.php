<?php
namespace socket;
  
/** 
 * Socket 
 * 
 * @author rogeriolino
 * @version 1.0.2
 */  
interface Socket {  
  
  
    /**
     * Binds to a socket
     * @throws SocketException
     */
    public function bind();
  
    /** 
     * Listens for a connection on a socket 
     * @throws SocketException 
     */  
    public function listen();
  
    /** 
     * Accepts a connection 
     * @throws SocketException 
     * @return Socket 
     */  
    public function accept();
  
    /** 
     * Reads data from the connected socket
     * @return string 
     */  
    public function read();
    
    /**
     * Receives data from a connected socket
     * @return string 
     */
    public function recv();
  
    /** 
     * Write the data to connected socket 
     * @param string $data 
     */  
    public function write($data);
  
    /** 
     * Initiates a connection on a socket 
     * @throws SocketException 
     * @return boolean 
     */  
    public function connect();
  
    /** 
     * Close the socket connection 
     */  
    public function close();
  
}

