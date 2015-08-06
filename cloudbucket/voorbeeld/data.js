var foldercount;

BucketData = function(url, elemid){
    var self = this;
    this.folders = new Array();
    this.files = new Array();
    this.url = url;
    this.elemid = elemid;
    this.name = "Amsterdam Smart City Hackaton - Files";
    
    self.loadFromUrl();
}

BucketData.prototype.loadFromUrl = function(){
    var self = this;
    $.get(self.url)
    .done(function(data) {
        // clear loading notice
        var xml = $(data);
        $.map(xml.find('Contents'), function(item) {
            item = $(item);
            self.add(item);
        });        

        self.update();        
    })
    .fail(function(error) {
        console.error(error);
    });
}

BucketData.prototype.add = function(item){
    var self = this;

    var Key = item.find('Key').text();
    
    if(Key.substring(Key.length -1) == "/"){
        //It's a folder!
        Key = Key.substring(0,Key.length - 1); // Remove last "/"
        path = Key.split("/");
        name = path[path.length - 1];
        var folder = new Folder(name, Key);
        folder.load(item);

        keysofar = "";
        var workingdir = this;
        for(i in path){
            if(i < path.length - 1){
                keysofar = path[i] + "/";
                dir = workingdir.getFolderByName(path[i]);
                if(!dir){
                    var newdir = new Folder(path[i], keysofar);
                    console.log(newdir);
                    workingdir.folders.push(newdir);
                    workingdir = workingdir.getFolderByName(path[i]);
                } else {
                    workingdir = dir;
                }
            }
        }
        workingdir.folders.push(folder);
    } else {
        //It's a file!
        path = Key.split("/");
        name = path[path.length - 1];
        var file = new File(name)
        file.load(item);

        var keysofar = "";
        var workingdir = this;
        for(var i in path){
            if(i < path.length - 1){
                keysofar += path[i] + "/";
                dir = workingdir.getFolderByName(path[i]);
                if(!dir){
                    var newdir = new Folder(path[i], keysofar);
                    workingdir.folders.push(newdir);
                    workingdir = workingdir.getFolderByName(path[i]);
                } else {
                    workingdir = dir;
                }
            }
        }
        
        if(name == "links.html"){
            $.get(Key)
            .done(function(data) {
                var xml = $(data);
                $.map(xml.find('LI'), function(item) {
                    item = $(item);
                    var link = item.html();
                    workingdir.links.push(link);
                    self.update();
                });                
            });
        } else if(name == "info.html"){
            workingdir.info = Key;
        } else {
            workingdir.files.push(file);
        }
   }
}

BucketData.prototype.update = function(){
    foldercount = 0;
    var self = this;
    var html = self.getHTML();
    $(self.elemid).html(html);
}

Folder = function(name, key){
    this.name = name;
    this.Key = key;
    this.LastModified = null;
    this.Size = 0;
    this.folders = new Array();
    this.files = new Array();
    this.links = new Array();
    this.info = null;
}

Folder.prototype.load = function(item){
    this.Key = item.find('Key').text();
    this.LastModified = item.find('LastModified').text();
    this.Size = item.find('Size').text();
}

var FolderGetHTML = function(){
    var self = this;
    var tempid = foldercount++;
    var html = "<div class='folder' id='folder" + tempid +"'><h3 onClick='$(\".sub" + tempid +"\").toggle();'><i class='fa fa-folder-o'></i> " + self.name + "</h3>";
    html += "<div class='folders sub" +tempid +"' id='folders" + tempid +"'>";
    if(self.info){
        html += "<a class='info' onClick='$(\"#infoframe\").attr(\"src\",\""+ self.info + "\"); return false;'><i class='fa fa-info'></i> More information on contents of this folder</a>";
    }
    for(var i in self.folders){
        html += self.folders[i].getHTML();
    }
    html += "</div>"
    html += "<div class='files sub" +tempid +"' id='files" + tempid +"'>";
    for(var i in self.files){
        html += self.files[i].getHTML();
    }
    html += "</div>";
    html += "<div class='links sub" +tempid +"' id='links" + tempid +"'>";
    for(var i in self.links){
        html += "<i class='fa fa-external-link'></i> " + self.links[i] + "</BR>";
    }
    html += "</div>";
    html+= "</div>";
    return html;
}

Folder.prototype.getHTML = FolderGetHTML;
BucketData.prototype.getHTML = FolderGetHTML;

File = function(name){
    this.name = name;
    this.Key = null;
    this.LastModified = null;
    this.Size = 0;
}

File.prototype.load = function(item){
    this.Key = item.find('Key').text();
    this.LastModified = item.find('LastModified').text();
    this.Size = item.find('Size').text();
}

File.prototype.getHTML = function(){
    var self = this;
    var html = "<a href='" + self.Key + "'><i class='fa fa-file'></i> " + self.name + "</a><BR/>";
    return html;
}

var getFolderByNameFunc = function(name){
    var self = this;
    for(i in self.folders){
        if(self.folders[i].name == name) return self.folders[i];
    }
}

BucketData.prototype.getFolderByName = getFolderByNameFunc;
File.prototype.getFolderByName = getFolderByNameFunc;
Folder.prototype.getFolderByName = getFolderByNameFunc;

projectname = "amsterdam_open_data";
url = "http://storage.googleapis.com/" + projectname;
//url = "data.xml";
bd = new BucketData(url, "#listing");
