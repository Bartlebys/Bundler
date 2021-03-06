<?php
include  FLEXIONS_MODULES_DIR . '/Bartleby/templates/localVariablesBindings.php';
require_once FLEXIONS_MODULES_DIR . '/Bartleby/templates/Requires.php';
require_once FLEXIONS_MODULES_DIR . '/Languages/FlexionsSwiftLang.php';

/* @var $f Flexed */
/* @var $d EntityRepresentation*/
/* @var $h Hypotypose */


if (isset( $f,$d,$h)) {
    // We use explicit name (!)
    // And reserve $f , $d , $h possibly for blocks

    /* @var $flexed Flexed*/
    /* @var $entityRepresentation EntityRepresentation*/
    /* @var $hypotypose Hypotypose*/

    $flexed=$f;
    $entityRepresentation=$d;
    $hypotypose=$h;

    // We determine the file name.
    $f->fileName = ucfirst(Pluralization::pluralize($d->name)) . 'ManagedCollection.swift';
    // And its package.
    $f->package = 'xOS/managedCollections/';

}else{
    return NULL;
}

/////////////////////
// EXCLUSIONS
/////////////////////

//Collection controllers are related to actions.

$exclusion = array();
$exclusionName = str_replace($h->classPrefix, '', $entityRepresentation->name);

$includeManagedCollection = false;
if (isset($xOSIncludeManagedCollectionForEntityNamed)) {
    foreach ($xOSIncludeManagedCollectionForEntityNamed as $inclusion) {
        if (strpos($exclusionName, $inclusion) !== false) {
            $includeManagedCollection = true;
        }

    }
    if (!$includeManagedCollection) {
        if (isset($excludeActionsWith)) {
            $exclusion = $excludeActionsWith;
        }
        foreach ($exclusion as $exclusionString) {
            if (strpos($exclusionName, $exclusionString) !== false) {
                return NULL; // We return null
            }
        }
    }
}

/////////////////////////
// VARIABLES COMPUTATION
/////////////////////////


$usesUrdMode=$d->usesUrdMode()==true;
$collectionControllerClass=ucfirst(Pluralization::pluralize($entityRepresentation->name)).'ManagedCollection';


// We just want to inject an item property Items
$virtualEntity=new EntityRepresentation();
$virtualEntity->name=$collectionControllerClass;
$itemsProperty=new PropertyRepresentation();
$itemsProperty->name="_items";
$itemsProperty->type=FlexionsTypes::COLLECTION;
$itemsProperty->instanceOf=ucfirst($entityRepresentation->name);
$itemsProperty->required=true;
$itemsProperty->isDynamic=true;
$itemsProperty->default=NULL;
$itemsProperty->isGeneratedType=true;

$virtualEntity->properties[]=$itemsProperty;
$blockRepresentation=$virtualEntity;

$blockEndContent="";// This is injected in the block

// EXPOSED
$exposedBlock='';
if ($modelsShouldConformToExposed){
    // We define the context for the block
    Registry::Instance()->defineVariables(['blockRepresentation'=>$blockRepresentation,'isBaseObject'=>false]);
    $exposedBlock=stringFromFile(FLEXIONS_MODULES_DIR.'/Bartleby/templates/blocks/Exposed.swift.block.php');
}

// MAPPABLE
$mappableBlock='';
if ($modelsShouldConformToMappable){
    $mappableblockEndContent='
            if map.mappingType == .fromJSON {
               forEach { $0.collection=self }
            }
';
    // We define the context for the block
    Registry::Instance()->defineVariables(['blockRepresentation'=>$blockRepresentation,'isBaseObject'=>false,'mappableblockEndContent'=>$mappableblockEndContent]);
    $mappableBlock=stringFromFile(FLEXIONS_MODULES_DIR.'/Bartleby/templates/blocks/Mappable.swift.block.php');
}
// NSSECURECODING
$secureCodingBlock='';
if($modelsShouldConformToNSSecureCoding) {
    // We define the context for the block
    Registry::Instance()->defineVariables(['blockRepresentation'=>$blockRepresentation,'isBaseObject'=>false,'decodingblockEndContent'=>'','encodingblockEndContent'=>'']);
    $secureCodingBlock=stringFromFile(FLEXIONS_MODULES_DIR.'/Bartleby/templates/blocks/NSSecureCoding.swift.block.php');
}


/////////////////////
// TEMPLATE
/////////////////////


?>
<?php echo GenerativeHelperForSwift::defaultHeader($f,$entityRepresentation); ?>

import Foundation
#if os(OSX)
import AppKit
#endif
#if !USE_EMBEDDED_MODULES
<?php
if (isset($isIncludeInBartlebysCommons) && $isIncludeInBartlebysCommons==true){
    echoIndent(cr(),0);
    echoIndent("import Alamofire",0);
    echoIndent("import ObjectMapper",0);
}else{
    echoIndent(cr(),0);
    echoIndent("import Alamofire",0);
    echoIndent("import ObjectMapper",0);
    echoIndent("import BartlebyKit",0);
}
?>
#endif

// MARK: A  collection controller of "<?php echo lcfirst(Pluralization::pluralize($entityRepresentation->name)); ?>"

// This controller implements data automation features.

@objc(<?php echo $collectionControllerClass ?>) open class <?php echo $collectionControllerClass ?> : <?php echo GenerativeHelperForSwift::getBaseClass($f,$entityRepresentation); ?>,IterableCollectibleCollection{

    // Universal type support
    override open class func typeName() -> String {
        return "<?php echo $collectionControllerClass ?>"
    }

    open var spaceUID:String {
        get{
            return self.document?.spaceUID ?? Default.NO_UID
        }
    }

    open var registryUID:String{
        get{
            return self.document?.UID ?? Default.NO_UID
        }
    }

    /// Init with prefetched content
    ///
    /// - parameter items: itels
    ///
    /// - returns: the instance
    required public init(items:[<?php echo ucfirst($entityRepresentation->name)?>]) {
        super.init()
        self._items=items
    }

    required public init() {
        super.init()
    }

    weak open var undoManager:UndoManager?

    #if os(OSX) && !USE_EMBEDDED_MODULES

    // We auto configure most of the array controller.
    open weak var arrayController:NSArrayController? {
        didSet{
            self.document?.setValue(self, forKey: "<?php echo lcfirst(Pluralization::pluralize($entityRepresentation->name)); ?>")
            arrayController?.objectClass=<?php echo ucfirst($entityRepresentation->name)?>.self
            arrayController?.entityName=<?php echo ucfirst($entityRepresentation->name)?>.className()
            arrayController?.bind("content", to: self, withKeyPath: "_items", options: nil)
        }
    }

    #endif

    weak open var tableView: BXTableView?

    // The underling _items storage
    fileprivate dynamic var _items:[<?php echo ucfirst($entityRepresentation->name)?>]=[<?php echo ucfirst($entityRepresentation->name)?>](){
        didSet {
            if _items != oldValue {
                self.provisionChanges(forKey: "_items",oldValue: oldValue,newValue: _items)
            }
        }
    }

    open func generate() -> AnyIterator<<?php echo ucfirst($entityRepresentation->name)?>> {
        var nextIndex = -1
        let limit=self._items.count-1
        return AnyIterator {
            nextIndex += 1
            if (nextIndex > limit) {
                return nil
            }
            return self._items[nextIndex]
        }
    }


    open subscript(index: Int) -> <?php echo ucfirst($entityRepresentation->name)?> {
        return self._items[index]
    }

    open var startIndex:Int {
        return 0
    }

    open var endIndex:Int {
        return self._items.count
    }

    /// Returns the position immediately after the given index.
    ///
    /// - Parameter i: A valid index of the collection. `i` must be less than
    ///   `endIndex`.
    /// - Returns: The index value immediately after `i`.
    open func index(after i: Int) -> Int {
        return i+1
    }


    open var count:Int {
        return self._items.count
    }

    open func indexOf(element:@escaping(<?php echo ucfirst($entityRepresentation->name)?>) throws -> Bool) rethrows -> Int?{
        return self._getIndexOf(element as! Collectible)
    }

    open func item(at index:Int)->Collectible?{
        return self[index]
    }


    fileprivate func _getIndexOf(_ item:Collectible)->Int?{
        if item.collectedIndex >= 0 {
            return item.collectedIndex
        }else{
            if let idx=_items.index(where:{return $0.UID == item.UID}){
                self[idx].collectedIndex=idx
                return idx
            }
        }
        return nil
    }

    fileprivate func _incrementIndexes(greaterThan lowerIndex:Int){
        let count=_items.count
        if count > lowerIndex{
            for i in lowerIndex...count-1{
                self[i].collectedIndex += 1
            }
        }
    }

    fileprivate func _decrementIndexes(greaterThan lowerIndex:Int){
        let count=_items.count
        if count > lowerIndex{
            for i in lowerIndex...count-1{
                self[i].collectedIndex -= 1
            }
        }
    }
    /**
    An iterator that permit dynamic approaches.
    The Registry ignores the real types.
    - parameter on: the closure
    */
    open func superIterate(_ on:@escaping(_ element: Collectible)->()){
        for item in self._items {
            on(item)
        }
    }

<?php if ($entityRepresentation->isDistantPersistencyOfCollectionAllowed()) {

   if ($entityRepresentation->groupedOnCommit()){
       echo('
    /**
    Commit all the changes in one bunch
    Marking commit on each item will toggle hasChanged flag.
    */
    open func commitChanges() -> [String] {
        var UIDS=[String]()
        if self.toBeCommitted{ // When one member has to be committed its collection _shouldBeCommited flag is turned to true
            let changedItems=self._items.filter { $0.toBeCommitted == true }
            bprint("\(changedItems.count) \( changedItems.count>1 ? "'.lcfirst(Pluralization::pluralize($entityRepresentation->name)).'" : "'.lcfirst($entityRepresentation->name).'" )  has changed in '.$collectionControllerClass.'",file:#file,function:#function,line:#line,category: Default.BPRINT_CATEGORY)
            if  changedItems.count > 0 {
                UIDS=changedItems.map({$0.UID})
               '.($usesUrdMode?'Upsert':'Update'). ucfirst(Pluralization::pluralize($entityRepresentation->name)) . '.commit(changedItems,inRegistryWithUID:self.registryUID)
            }
            self.committed=true
         }
        return UIDS
    }
');
   }else{
       echo('
    /**
    Commit all the changes in one bunch
    Marking commit on each item will toggle hasChanged flag.
    */
    open func commitChanges() -> [String] {
        var UIDS=[String]()
        if self.toBeCommitted{ // When one member has to be committed its collection _shouldBeCommited flag is turned to true
            let changedItems=self._items.filter { $0.toBeCommitted == true }
            bprint("\(changedItems.count) \( changedItems.count>1 ? "'.lcfirst(Pluralization::pluralize($entityRepresentation->name)).'" : "'.lcfirst($entityRepresentation->name).'" )  has changed in '.$collectionControllerClass.'",file:#file,function:#function,line:#line,category: Default.BPRINT_CATEGORY)
            for changed in changedItems{
                UIDS.append(changed.UID)
                '.($usesUrdMode?'Upsert':'Update'). ucfirst($entityRepresentation->name) . '.commit(changed, inRegistryWithUID:self.registryUID)
            }
            self.committed=true
        }
        return UIDS
    }
');
   }




}else{
echo('

    /**
     Commit is ignored because
     Distant persistency is not allowed for '.$entityRepresentation->name.'
    */
    open func commitChanges() ->[String] {
        return [String]()
    }
    
');
}
?>

    // MARK: Identifiable

    override open class var collectionName:String{
        return <?php echo ucfirst($entityRepresentation->name)?>.collectionName
    }

    override open var d_collectionName:String{
        return <?php echo ucfirst($entityRepresentation->name)?>.collectionName
    }

<?php echo $exposedBlock ?>
<?php echo $mappableBlock ?>
<?php echo $secureCodingBlock ?>

    // MARK: Upsert


    open func upsert(_ item: Collectible, commit:Bool=true){
        if let idx=_items.index(where:{return $0.UID == item.UID}){
            // it is an update
            // we must patch it
            let currentInstance=_items[idx]
            if commit==false{
                // When upserting from a trigger
                // We do not want to produce Larsen effect on data.
                // So we lock the auto commit observer before applying the patch
                // And we unlock the autoCommit Observer after the patch.
                currentInstance.doNotCommit {
                    try? currentInstance.mergeWith(item)
                }
            }else{
                try? currentInstance.mergeWith(item)
            }
        }else{
            // It is a creation
            self.add(item, commit:commit)
        }
    }

    // MARK: Add


    open func add(_ item:Collectible, commit:Bool=true){
        self.insertObject(item, inItemsAtIndex: _items.count, commit:commit)
    }

    // MARK: Insert

    /**
    Inserts an object at a given index into the collection.

    - parameter item:   the item
    - parameter index:  the index in the collection (not the ArrayController arranged object)
    - parameter commit: should we commit the insertion?
    */
    open func insertObject(_ item: Collectible, inItemsAtIndex index: Int, commit:Bool=true) {
        if let item=item as? <?php echo ucfirst($entityRepresentation->name)?>{

            item.collection = self // Reference the collection
            item.collectedIndex = index // Update the index
            self._incrementIndexes(greaterThan:index)

<?php if ($entityRepresentation->isUndoable()) {
    echo('
            if let undoManager = self.undoManager{
                // Has an edit occurred already in this event?
                if undoManager.groupingLevel > 0 {
                    // Close the last group
                    undoManager.endUndoGrouping()
                    // Open a new group
                    undoManager.beginUndoGrouping()
                }
            }

            // Add the inverse of this invocation to the undo stack
            if let undoManager: UndoManager = undoManager {
                (undoManager.prepare(withInvocationTarget: self) as AnyObject).removeObjectFromItemsAtIndex(index, commit:commit)
                if !undoManager.isUndoing {
                    undoManager.setActionName(NSLocalizedString("Add' . ucfirst($entityRepresentation->name) . '", comment: "Add' . ucfirst($entityRepresentation->name) . ' undo action"))
                }
            }
            ');
}
?>
            // Insert the item
            self._items.insert(item, at: index)
            #if os(OSX) && !USE_EMBEDDED_MODULES
            if let arrayController = self.arrayController{

                // Re-arrange (in case the user has sorted a column)
                arrayController.rearrangeObjects()

                if let tableView = self.tableView{
                    DispatchQueue.main.async(execute: {
                        let sorted=self.arrayController?.arrangedObjects as! [<?php echo ucfirst($entityRepresentation->name)?>]
                        // Find the object just added
                        if let row=sorted.index(where:{ $0.UID==item.UID }){
                            // Start editing
                            tableView.editColumn(0, row: row, with: nil, select: true)
                        }
                    })
                }
            }
            #endif

<?php if ($entityRepresentation->isDistantPersistencyOfCollectionAllowed()){
    echo("
            if item.committed==false && commit==true{
               ".($usesUrdMode?'Upsert':'Create').$entityRepresentation->name.".commit(item, inRegistryWithUID:self.registryUID)
            }".cr());
}else{
        echo('
            // Commit is ignored because
            // Distant persistency is not allowed for '.$entityRepresentation->name.'
            ');
}
?>

        }else{

        }
    }




    // MARK: Remove

    /**
    Removes an object at a given index from the collection.

    - parameter index:  the index in the collection (not the ArrayController arranged object)
    - parameter commit: should we commit the removal?
    */
    open func removeObjectFromItemsAtIndex(_ index: Int, commit:Bool=true) {
       let item : <?php echo ucfirst($entityRepresentation->name)?> =  self[index]
        self._decrementIndexes(greaterThan:index)
<?php if ($entityRepresentation->isUndoable()) {
echo(
'
        // Add the inverse of this invocation to the undo stack
        if let undoManager: UndoManager = undoManager {
            // We don\'t want to introduce a retain cycle
            // But with the objc magic casting undoManager.prepareWithInvocationTarget(self) as? UsersManagedCollection fails
            // That\'s why we have added an registerUndo extension on UndoManager
            undoManager.registerUndo({ () -> Void in
               self.insertObject(item, inItemsAtIndex: index, commit:commit)
            })
            if !undoManager.isUndoing {
                undoManager.setActionName(NSLocalizedString("Remove' . ucfirst($entityRepresentation->name) . '", comment: "Remove ' . ucfirst($entityRepresentation->name) . ' undo action"))
            }
        }
        ');
}
?>

        // Unregister the item
        Registry.unRegister(item)

        //Update the commit flag
        item.committed=false

        // Remove the item from the collection
        self._items.remove(at:index)

    <?php if ($entityRepresentation->isDistantPersistencyOfCollectionAllowed()) {
        echo('
        if commit==true{
            Delete'.$entityRepresentation->name.'.commit(item,fromRegistryWithUID:self.registryUID) 
        }'.cr());
    }else{
        echo('
        // Commit is ignored because
        // Distant persistency is not allowed for '.$entityRepresentation->name.'
        ');
    }
    ?>
    }


    open func removeObjects(_ _items: [Collectible],commit:Bool=true){
        for item in self._items{
            self.removeObject(item,commit:commit)
        }
    }

    open func removeObject(_ item: Collectible, commit:Bool=true){
        if let instance=item as? <?php echo(ucfirst($entityRepresentation->name))?>{
            if let idx=self._getIndexOf(instance){
                self.removeObjectFromItemsAtIndex(idx, commit:commit)
            }
        }
    }

    open func removeObjectWithIDS(_ ids: [String],commit:Bool=true){
        for uid in ids{
            self.removeObjectWithID(uid,commit:commit)
        }
    }

    open func removeObjectWithID(_ id:String, commit:Bool=true){
        if let idx=self.index(where:{ return $0.UID==id } ){
            self.removeObjectFromItemsAtIndex(idx, commit:commit)
        }
    }
}